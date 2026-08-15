<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Booking\Infrastructure\Doctrine\BookingRepository;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\OrganizerApplicationStatus;
use App\Identity\Domain\Enum\Role;
use App\Identity\Infrastructure\Doctrine\OrganizerApplicationRepository;
use App\Identity\Infrastructure\Doctrine\UserRepository;
use App\Shared\Application\Message\SendTransactionalEmail;
use App\Shared\Domain\Enum\SupportedLocale;
use App\Workshop\Domain\Entity\WorkshopTranslation;
use App\Workshop\Domain\Enum\WorkshopStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class UserJourneysTest extends WebTestCase
{
    public function testParticipantCanLogInWithTheRealForm(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/fr/connexion');
        $client->submit($crawler->selectButton('Se connecter')->form([
            'email' => 'participant@skillspot.local',
            'password' => 'SkillSpot2026!',
        ]));

        self::assertResponseRedirects('/fr');
        $client->request('GET', '/fr/tableau-de-bord');
        self::assertResponseIsSuccessful();
    }

    public function testRegistrationPersistsAUserAndQueuesVerificationEmail(): void
    {
        [$client, $entityManager] = $this->transactionalClient();

        try {
            $crawler = $client->request('GET', '/en/register');
            $client->submit($crawler->selectButton('Create my account')->form([
                'registration_form[firstName]' => 'Nina',
                'registration_form[lastName]' => 'Test',
                'registration_form[email]' => 'nina.test@skillspot.test',
                'registration_form[plainPassword][first]' => 'SecureTest2026!',
                'registration_form[plainPassword][second]' => 'SecureTest2026!',
                'registration_form[agreeTerms]' => '1',
            ]));

            self::assertResponseRedirects('/en/login');
            $user = self::getContainer()->get(UserRepository::class)->findByEmail('nina.test@skillspot.test');
            self::assertInstanceOf(User::class, $user);
            self::assertFalse($user->isVerified());
            self::assertSame(SupportedLocale::English, $user->getPreferredLocale());
            $transport = self::getContainer()->get('messenger.transport.async');
            self::assertInstanceOf(InMemoryTransport::class, $transport);
            self::assertCount(1, $transport->getSent());
            $message = $transport->getSent()[0]->getMessage();
            self::assertInstanceOf(SendTransactionalEmail::class, $message);
            self::assertSame('en', $message->locale);
            self::assertStringContainsString('/en/verify-email/', (string) $message->actionUrl);
        } finally {
            $this->rollBack($entityManager);
        }
    }

    public function testParticipantCanBookAnAvailableSessionWithCsrfProtection(): void
    {
        [$client, $entityManager] = $this->transactionalClient();

        try {
            $candidate = self::getContainer()->get(UserRepository::class)->findByEmail('candidate@skillspot.local');
            self::assertInstanceOf(User::class, $candidate);
            $client->loginUser($candidate);
            $crawler = $client->request('GET', '/en/workshops/symfony-without-black-magic');
            $client->submit($crawler->selectButton('Book my seat')->form());

            self::assertResponseRedirects('/en/dashboard');
            self::assertCount(1, self::getContainer()->get(BookingRepository::class)->forUser($candidate));
            $transport = self::getContainer()->get('messenger.transport.async');
            self::assertInstanceOf(InMemoryTransport::class, $transport);
            $emails = array_values(array_filter(
                $transport->getSent(),
                static fn ($envelope): bool => $envelope->getMessage() instanceof SendTransactionalEmail,
            ));
            self::assertCount(1, $emails);
            $email = $emails[0]->getMessage();
            self::assertInstanceOf(SendTransactionalEmail::class, $email);
            self::assertSame('en', $email->locale);
            self::assertStringContainsString('/en/dashboard', (string) $email->actionUrl);
            $client->followRedirect();
            self::assertSelectorTextContains('.flash-success', 'Your seat is confirmed');
        } finally {
            $this->rollBack($entityManager);
        }
    }

    public function testOrganizerCanCreateADraftWorkshop(): void
    {
        [$client, $entityManager] = $this->transactionalClient();

        try {
            $organizer = self::getContainer()->get(UserRepository::class)->findByEmail('organizer@skillspot.local');
            self::assertInstanceOf(User::class, $organizer);
            $client->loginUser($organizer);
            $crawler = $client->request('GET', '/en/organizer/workshops/new');
            $client->submit($crawler->selectButton('Save workshop')->form([
                'workshop[titleFr]' => 'Architecture testable avec Symfony',
                'workshop[descriptionFr]' => 'Un atelier pratique suffisamment détaillé pour apprendre à isoler le domaine, orchestrer les cas d’usage et tester les frontières techniques sans complexité accidentelle.',
                'workshop[titleEn]' => 'Testable architecture with Symfony',
                'workshop[descriptionEn]' => 'A sufficiently detailed hands-on workshop to learn how to isolate the domain, orchestrate use cases and test technical boundaries without accidental complexity.',
                'workshop[category]' => 'development',
                'workshop[level]' => 'intermediate',
            ]));

            self::assertResponseRedirects();
            self::assertMatchesRegularExpression('#/en/organizer/workshops/\d+/sessions/new$#', (string) $client->getResponse()->headers->get('Location'));
            $translation = $entityManager->getRepository(WorkshopTranslation::class)->findOneBy(['slug' => 'testable-architecture-with-symfony']);
            self::assertInstanceOf(WorkshopTranslation::class, $translation);
            $workshop = $translation->getWorkshop();
            self::assertSame(WorkshopStatus::Draft, $workshop->getStatus());
            self::assertSame('architecture-testable-avec-symfony', $workshop->translation('fr')->getSlug());
        } finally {
            $this->rollBack($entityManager);
        }
    }

    public function testAdministratorCanApproveAnOrganizerApplication(): void
    {
        [$client, $entityManager] = $this->transactionalClient();

        try {
            $admin = self::getContainer()->get(UserRepository::class)->findByEmail('admin@skillspot.local');
            self::assertInstanceOf(User::class, $admin);
            $client->loginUser($admin);
            $crawler = $client->request('GET', '/en/admin/organizer-applications');
            $client->submit($crawler->selectButton('Approve')->form(['note' => 'Profile approved by the functional scenario.']));

            self::assertResponseRedirects('/en/admin/organizer-applications');
            $application = self::getContainer()->get(OrganizerApplicationRepository::class)->findOneBy([]);
            self::assertNotNull($application);
            self::assertSame(OrganizerApplicationStatus::Approved, $application->getStatus());
            self::assertContains(Role::Organizer->value, $application->getApplicant()->getRoles());
        } finally {
            $this->rollBack($entityManager);
        }
    }

    /** @return array{0: \Symfony\Bundle\FrameworkBundle\KernelBrowser, 1: EntityManagerInterface} */
    private function transactionalClient(): array
    {
        $client = static::createClient();
        $client->disableReboot();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->beginTransaction();

        return [$client, $entityManager];
    }

    private function rollBack(EntityManagerInterface $entityManager): void
    {
        if ($entityManager->getConnection()->isTransactionActive()) {
            $entityManager->getConnection()->rollBack();
        }
        $entityManager->clear();
    }
}
