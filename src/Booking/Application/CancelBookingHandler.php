<?php

declare(strict_types=1);

namespace App\Booking\Application;

use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Enum\BookingStatus;
use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Identity\Domain\Entity\User;
use App\Shared\Application\Notification\TransactionalNotifierInterface;
use App\Shared\Application\Realtime\AvailabilityPublisherInterface;
use App\Shared\Application\TransactionManagerInterface;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use App\Workshop\Domain\Repository\WorkshopSessionRepositoryInterface;
use Symfony\Component\Clock\ClockInterface;

final readonly class CancelBookingHandler
{
    public function __construct(
        private TransactionManagerInterface $transactions,
        private WorkshopSessionRepositoryInterface $sessions,
        private BookingRepositoryInterface $bookings,
        private TransactionalNotifierInterface $notifier,
        private AvailabilityPublisherInterface $availability,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(int $bookingId, User $attendee): void
    {
        $now = $this->clock->now();
        $result = $this->transactions->run(function () use ($bookingId, $attendee, $now): array {
            $booking = $this->bookings->get($bookingId);
            if (!$booking instanceof Booking || $booking->getAttendee() !== $attendee) {
                throw new BusinessRuleViolation('booking.error.not_found');
            }

            $session = $this->sessions->lock((int) $booking->getSession()->getId());
            $wasConfirmed = BookingStatus::Confirmed === $booking->getStatus();
            $booking->cancelByAttendee($now);
            $this->bookings->save($booking);
            $promoted = $wasConfirmed ? $this->bookings->firstWaitlisted($session) : null;
            if ($promoted) {
                $promoted->promote($now);
                $this->bookings->save($promoted);
            }

            return [$booking, $promoted];
        });

        [$booking, $promoted] = $result;
        $this->notifier->bookingCancelled($booking);
        if ($promoted instanceof Booking) {
            $this->notifier->bookingPromoted($promoted);
        }
        $this->availability->publish($booking->getSession());
    }
}
