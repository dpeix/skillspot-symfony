<?php

declare(strict_types=1);

namespace App\Booking\Domain\Repository;

use App\Booking\Domain\Entity\Booking;
use App\Identity\Domain\Entity\User;
use App\Workshop\Domain\Entity\WorkshopSession;

interface BookingRepositoryInterface
{
    public function get(int $id): ?Booking;

    public function findFor(User $attendee, WorkshopSession $session): ?Booking;

    public function hasActiveOverlap(User $attendee, WorkshopSession $session): bool;

    public function countConfirmed(WorkshopSession $session): int;

    public function firstWaitlisted(WorkshopSession $session): ?Booking;

    /** @return list<Booking> */
    public function forUser(User $attendee): array;

    /** @return list<Booking> */
    public function forSession(WorkshopSession $session): array;

    /** @return array{bookings: int, confirmed: int, waitlisted: int, cancelled: int, attended: int, no_show: int} */
    public function statisticsForOrganizer(User $organizer): array;

    /** @return list<Booking> */
    public function remindersDue(\DateTimeImmutable $from, \DateTimeImmutable $until): array;

    public function save(Booking $booking): void;
}
