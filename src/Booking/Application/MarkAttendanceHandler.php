<?php

declare(strict_types=1);

namespace App\Booking\Application;

use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Enum\AttendanceStatus;
use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Identity\Domain\Entity\User;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use Symfony\Component\Clock\ClockInterface;

final readonly class MarkAttendanceHandler
{
    public function __construct(
        private BookingRepositoryInterface $bookings,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(Booking $booking, User $organizer, AttendanceStatus $attendance): void
    {
        if ($booking->getSession()->getWorkshop()->getOwner() !== $organizer) {
            throw new BusinessRuleViolation('Vous ne pouvez pas émarger cette réservation.');
        }

        $booking->markAttendance($attendance, $this->clock->now());
        $this->bookings->save($booking);
    }
}
