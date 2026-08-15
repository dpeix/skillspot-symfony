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

final readonly class BookSessionHandler
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

    public function __invoke(int $sessionId, User $attendee): Booking
    {
        $now = $this->clock->now();
        $booking = $this->transactions->run(function () use ($sessionId, $attendee, $now): Booking {
            $session = $this->sessions->lock($sessionId);
            if (!$session->isBookableAt($now)) {
                throw new BusinessRuleViolation('booking.error.session_unbookable');
            }

            $existing = $this->bookings->findFor($attendee, $session);
            if ($existing && BookingStatus::Cancelled !== $existing->getStatus()) {
                throw new BusinessRuleViolation('booking.error.duplicate');
            }
            if ($this->bookings->hasActiveOverlap($attendee, $session)) {
                throw new BusinessRuleViolation('booking.error.overlap');
            }

            $status = $this->bookings->countConfirmed($session) < $session->getCapacity()
                ? BookingStatus::Confirmed
                : BookingStatus::Waitlisted;

            if ($existing) {
                $existing->reactivate($status, $now);
                $booking = $existing;
            } else {
                $booking = new Booking($attendee, $session, $status, $now);
            }
            $this->bookings->save($booking);

            return $booking;
        });

        $this->notifier->bookingCreated($booking);
        $this->availability->publish($booking->getSession());

        return $booking;
    }
}
