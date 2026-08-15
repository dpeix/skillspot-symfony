<?php

declare(strict_types=1);

namespace App\Booking\Infrastructure\Doctrine;

use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Enum\AttendanceStatus;
use App\Booking\Domain\Enum\BookingStatus;
use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Identity\Domain\Entity\User;
use App\Workshop\Domain\Entity\WorkshopSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Booking> */
final class BookingRepository extends ServiceEntityRepository implements BookingRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function get(int $id): ?Booking
    {
        return $this->find($id);
    }

    public function findFor(User $attendee, WorkshopSession $session): ?Booking
    {
        return $this->findOneBy(['attendee' => $attendee, 'session' => $session]);
    }

    public function hasActiveOverlap(User $attendee, WorkshopSession $session): bool
    {
        return 0 < (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->join('b.session', 's')
            ->andWhere('b.attendee = :attendee')
            ->andWhere('b.status != :cancelled')
            ->andWhere('s.startsAt < :endsAt AND s.endsAt > :startsAt')
            ->setParameter('attendee', $attendee)
            ->setParameter('cancelled', BookingStatus::Cancelled)
            ->setParameter('endsAt', $session->getEndsAt())
            ->setParameter('startsAt', $session->getStartsAt())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countConfirmed(WorkshopSession $session): int
    {
        return (int) $this->count(['session' => $session, 'status' => BookingStatus::Confirmed]);
    }

    public function firstWaitlisted(WorkshopSession $session): ?Booking
    {
        return $this->findOneBy(
            ['session' => $session, 'status' => BookingStatus::Waitlisted],
            ['queuedAt' => 'ASC', 'id' => 'ASC'],
        );
    }

    public function forUser(User $attendee): array
    {
        /** @var list<Booking> $result */
        $result = $this->createQueryBuilder('b')
            ->addSelect('s', 'w')
            ->join('b.session', 's')
            ->join('s.workshop', 'w')
            ->andWhere('b.attendee = :attendee')
            ->setParameter('attendee', $attendee)
            ->orderBy('s.startsAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function forSession(WorkshopSession $session): array
    {
        return $this->findBy(['session' => $session], ['status' => 'ASC', 'queuedAt' => 'ASC']);
    }

    public function statisticsForOrganizer(User $organizer): array
    {
        /** @var list<array{status: BookingStatus|string, attendance: AttendanceStatus|string, total: int|string}> $rows */
        $rows = $this->createQueryBuilder('b')
            ->select('b.status AS status', 'b.attendance AS attendance', 'COUNT(b.id) AS total')
            ->join('b.session', 's')
            ->join('s.workshop', 'w')
            ->andWhere('w.owner = :organizer')
            ->setParameter('organizer', $organizer)
            ->groupBy('b.status', 'b.attendance')
            ->getQuery()
            ->getArrayResult();

        $stats = ['bookings' => 0, 'confirmed' => 0, 'waitlisted' => 0, 'cancelled' => 0, 'attended' => 0, 'no_show' => 0];
        foreach ($rows as $row) {
            $total = (int) $row['total'];
            $status = $row['status'] instanceof BookingStatus ? $row['status']->value : (string) $row['status'];
            $attendance = $row['attendance'] instanceof AttendanceStatus ? $row['attendance']->value : (string) $row['attendance'];
            $stats['bookings'] += $total;
            match ($status) {
                BookingStatus::Confirmed->value => $stats['confirmed'] += $total,
                BookingStatus::Waitlisted->value => $stats['waitlisted'] += $total,
                BookingStatus::Cancelled->value => $stats['cancelled'] += $total,
                default => null,
            };
            if (AttendanceStatus::Attended->value === $attendance) {
                $stats['attended'] += $total;
            } elseif (AttendanceStatus::NoShow->value === $attendance) {
                $stats['no_show'] += $total;
            }
        }

        return $stats;
    }

    public function remindersDue(\DateTimeImmutable $from, \DateTimeImmutable $until): array
    {
        /** @var list<Booking> $result */
        $result = $this->createQueryBuilder('b')
            ->addSelect('a', 's', 'w')
            ->join('b.attendee', 'a')
            ->join('b.session', 's')
            ->join('s.workshop', 'w')
            ->andWhere('b.status = :confirmed')
            ->andWhere('b.reminderSentAt IS NULL')
            ->andWhere('s.startsAt >= :from AND s.startsAt < :until')
            ->setParameter('confirmed', BookingStatus::Confirmed)
            ->setParameter('from', $from)
            ->setParameter('until', $until)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function save(Booking $booking): void
    {
        $this->getEntityManager()->persist($booking);
        $this->getEntityManager()->flush();
    }
}
