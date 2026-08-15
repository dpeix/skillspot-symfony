<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Identity\Domain\Entity\User;
use App\Identity\Infrastructure\Doctrine\UserRepository;
use App\Shared\Domain\Enum\SupportedLocale;
use App\Workshop\Application\PublishWorkshopHandler;
use App\Workshop\Domain\Entity\Workshop;
use App\Workshop\Domain\Entity\WorkshopSession;
use App\Workshop\Domain\Enum\WorkshopCategory;
use App\Workshop\Domain\Enum\WorkshopLevel;
use App\Workshop\Domain\Enum\WorkshopMode;
use App\Workshop\Domain\Enum\WorkshopStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WorkflowUseCaseTest extends KernelTestCase
{
    public function testAWorkshopWithAFutureSessionCanBePublished(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->beginTransaction();

        try {
            $owner = self::getContainer()->get(UserRepository::class)->findByEmail('organizer@skillspot.local');
            self::assertInstanceOf(User::class, $owner);
            $workshop = new Workshop(
                $owner,
                WorkshopCategory::Development,
                WorkshopLevel::Intermediate,
            );
            $workshop->addTranslation(SupportedLocale::French, 'Atelier prêt à publier', 'atelier-pret-a-publier', 'Une description volontairement détaillée qui prouve que cet atelier contient assez d’informations utiles pour être publié en toute sécurité.');
            $workshop->addTranslation(SupportedLocale::English, 'Workshop ready to publish', 'workshop-ready-to-publish', 'A deliberately detailed description that proves the workshop contains enough useful information to be published safely.');
            new WorkshopSession(
                $workshop,
                new \DateTimeImmutable('2030-01-03 10:00 UTC'),
                new \DateTimeImmutable('2030-01-03 12:00 UTC'),
                12,
                WorkshopMode::Online,
                meetingUrl: 'https://meet.example.test/publish',
            );

            self::getContainer()->get(PublishWorkshopHandler::class)($workshop);

            self::assertSame(WorkshopStatus::Published, $workshop->getStatus());
        } finally {
            if ($entityManager->getConnection()->isTransactionActive()) {
                $entityManager->getConnection()->rollBack();
            }
        }
    }
}
