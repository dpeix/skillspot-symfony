<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Identity\Domain\Entity\User;
use App\Identity\Infrastructure\Doctrine\UserRepository;
use App\Shared\Domain\Enum\SupportedLocale;
use App\Workshop\Infrastructure\Doctrine\WorkshopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class WorkshopTranslationRepositoryTest extends KernelTestCase
{
    public function testPublishedWorkshopCanBeFoundByLocale(): void
    {
        self::bootKernel();
        $repository = self::getContainer()->get(WorkshopRepository::class);

        $workshop = $repository->findPublishedBySlug(SupportedLocale::English, 'symfony-without-black-magic');

        self::assertNotNull($workshop);
        self::assertSame('Symfony without black magic', $workshop->translation(SupportedLocale::English)->getTitle());
        self::assertSame('Symfony sans magie noire', $workshop->translation(SupportedLocale::French)->getTitle());
    }

    public function testCatalogAndOrganizerQueriesEagerLoadRenderedCollections(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $repository = self::getContainer()->get(WorkshopRepository::class);

        $workshop = $repository->searchPublished([], 1)[0];
        self::assertInstanceOf(PersistentCollection::class, $workshop->getTranslations());
        self::assertTrue($workshop->getTranslations()->isInitialized());
        self::assertInstanceOf(PersistentCollection::class, $workshop->getSessions());
        self::assertTrue($workshop->getSessions()->isInitialized());
        foreach ($workshop->getSessions() as $session) {
            self::assertInstanceOf(PersistentCollection::class, $session->getBookings());
            self::assertTrue($session->getBookings()->isInitialized());
        }

        $entityManager->clear();
        $owner = self::getContainer()->get(UserRepository::class)->findByEmail('organizer@skillspot.local');
        self::assertInstanceOf(User::class, $owner);
        $ownedWorkshop = $repository->ownedBy($owner)[0];
        self::assertTrue($ownedWorkshop->getTranslations()->isInitialized());
        self::assertTrue($ownedWorkshop->getSessions()->isInitialized());
    }
}
