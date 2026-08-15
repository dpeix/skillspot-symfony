<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Booking\Infrastructure\Doctrine\BookingRepository;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\OrganizerApplicationStatus;
use App\Identity\Domain\Enum\Role;
use App\Identity\Infrastructure\Doctrine\OrganizerApplicationRepository;
use App\Identity\Infrastructure\Doctrine\UserRepository;
use App\Workshop\Domain\Enum\WorkshopStatus;
use App\Workshop\Infrastructure\Doctrine\WorkshopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class UserJourneysTest extends WebTestCase
{
    public function testParticipantCanLogInWithTheRealForm(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/connexion');
        $client->submit($crawler->selectButton('Se connecter')->form([
            'email' => 'participant@skillspot.local',
            'password' => 'SkillSpot2026!',
        ]));

        self::assertResponseRedirects('/');
        $client->request('GET', '/dashboard');
        self::assertResponseIsSuccessful();
    }

    public function testRegistrationPersistsAUserAndQueuesVerificationEmail(): void
    {
        [$client, $entityManager] = $this->transactionalClient();

        try {
            $crawler = $client->request('GET', '/inscription');
            $client->submit($crawler->selectButton('Créer mon compte')->form([
                'registration_form[firstName]' => 'Nina',
                'registration_form[lastName]' => 'Test',
                'registration_form[email]' => 'nina.test@skillspot.test',
                'registration_form[plainPassword][first]' => 'SecureTest2026!',
                'registration_form[plainPassword][second]' => 'SecureTest2026!',
                'registration_form[agreeTerms]' => '1',
            ]));

            self::assertResponseRedirects('/connexion');
            $user = self::getContainer()->get(UserRepository::class)->findByEmail('nina.test@skillspot.test');
            self::assertInstanceOf(User::class, $user);
            self::assertFalse($user->isVerified());
            $transport = self::getContainer()->get('messenger.transport.async');
            self::assertInstanceOf(InMemoryTransport::class, $transport);
            self::assertCount(1, $transport->getSent());
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
            $crawler = $client->request('GET', '/ateliers/symfony-sans-magie-noire');
            $client->submit($crawler->selectButton('Réserver ma place')->form());

            self::assertResponseRedirects('/dashboard');
            $client->followRedirect();
            self::assertSelectorTextContains('.flash-success', 'Votre place est confirmée');
            self::assertCount(1, self::getContainer()->get(BookingRepository::class)->forUser($candidate));
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
            $crawler = $client->request('GET', '/organizer/workshops/new');
            $client->submit($crawler->selectButton('Enregistrer l’atelier')->form([
                'workshop[title]' => 'Architecture testable avec Symfony',
                'workshop[description]' => 'Un atelier pratique suffisamment détaillé pour apprendre à isoler le domaine, orchestrer les cas d’usage et tester les frontières techniques sans complexité accidentelle.',
                'workshop[category]' => 'development',
                'workshop[level]' => 'intermediate',
            ]));

            self::assertResponseRedirects();
            self::assertMatchesRegularExpression('#/organizer/workshops/\d+/sessions/new$#', (string) $client->getResponse()->headers->get('Location'));
            $workshop = self::getContainer()->get(WorkshopRepository::class)->findOneBy(['slug' => 'architecture-testable-avec-symfony']);
            self::assertNotNull($workshop);
            self::assertSame(WorkshopStatus::Draft, $workshop->getStatus());
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
            $crawler = $client->request('GET', '/admin/organizer-applications');
            $client->submit($crawler->selectButton('Accepter')->form(['note' => 'Profil validé par le scénario fonctionnel.']));

            self::assertResponseRedirects('/admin/organizer-applications');
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
