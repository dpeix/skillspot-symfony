<?php

declare(strict_types=1);

namespace App\Tests\Integration\Booking;

use App\Booking\Application\BookSessionHandler;
use App\Booking\Application\CancelBookingHandler;
use App\Booking\Domain\Enum\BookingStatus;
use App\Booking\Infrastructure\Doctrine\BookingRepository;
use App\Identity\Domain\Entity\User;
use App\Identity\Infrastructure\Doctrine\UserRepository;
use App\Workshop\Domain\Entity\WorkshopSession;
use App\Workshop\Infrastructure\Doctrine\WorkshopSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BookingUseCaseTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->getConnection()->rollBack();
        }
        parent::tearDown();
    }

    public function testAFullSessionCreatesAWaitlistedBooking(): void
    {
        $sessions = self::getContainer()->get(WorkshopSessionRepository::class);
        $session = $sessions->findOneBy(['capacity' => 2]);
        self::assertInstanceOf(WorkshopSession::class, $session);

        $attendee = new User('integration@example.test', 'Integration', 'Tester', 'hashed');
        $attendee->verify();
        $this->entityManager->persist($attendee);
        $this->entityManager->flush();

        $booking = self::getContainer()->get(BookSessionHandler::class)((int) $session->getId(), $attendee);

        self::assertSame(BookingStatus::Waitlisted, $booking->getStatus());
    }

    public function testCancellingAConfirmedSeatPromotesTheFirstWaitlistedMember(): void
    {
        $users = self::getContainer()->get(UserRepository::class);
        $bookings = self::getContainer()->get(BookingRepository::class);
        $sessions = self::getContainer()->get(WorkshopSessionRepository::class);
        $participant = $users->findByEmail('participant@skillspot.local');
        $waiting = $users->findByEmail('karim@skillspot.local');
        $session = $sessions->findOneBy(['capacity' => 2]);
        self::assertInstanceOf(User::class, $participant);
        self::assertInstanceOf(User::class, $waiting);
        self::assertInstanceOf(WorkshopSession::class, $session);
        $confirmed = $bookings->findFor($participant, $session);
        self::assertNotNull($confirmed);

        self::getContainer()->get(CancelBookingHandler::class)((int) $confirmed->getId(), $participant);

        self::assertSame(BookingStatus::Confirmed, $bookings->findFor($waiting, $session)?->getStatus());
    }

    public function testOrganizerStatisticsAreComputedByTheRepository(): void
    {
        $users = self::getContainer()->get(UserRepository::class);
        $organizer = $users->findByEmail('organizer@skillspot.local');
        self::assertInstanceOf(User::class, $organizer);

        $statistics = self::getContainer()->get(BookingRepository::class)->statisticsForOrganizer($organizer);

        self::assertSame([
            'bookings' => 5,
            'confirmed' => 4,
            'waitlisted' => 1,
            'cancelled' => 0,
            'attended' => 1,
            'no_show' => 1,
        ], $statistics);
    }
}
