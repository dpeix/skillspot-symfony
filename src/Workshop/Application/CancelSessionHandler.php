<?php

declare(strict_types=1);

namespace App\Workshop\Application;

use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Enum\BookingStatus;
use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Identity\Domain\Entity\User;
use App\Shared\Application\Notification\TransactionalNotifierInterface;
use App\Shared\Application\Realtime\AvailabilityPublisherInterface;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use App\Workshop\Domain\Entity\WorkshopSession;
use App\Workshop\Domain\Repository\WorkshopSessionRepositoryInterface;
use Symfony\Component\Clock\ClockInterface;

final readonly class CancelSessionHandler
{
    public function __construct(
        private WorkshopSessionRepositoryInterface $sessions,
        private BookingRepositoryInterface $bookings,
        private TransactionalNotifierInterface $notifier,
        private AvailabilityPublisherInterface $availability,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(WorkshopSession $session, User $organizer): void
    {
        if ($session->getWorkshop()->getOwner() !== $organizer) {
            throw new BusinessRuleViolation('session.error.cannot_cancel');
        }

        $session->cancel();
        $affected = array_values(array_filter(
            $this->bookings->forSession($session),
            static fn (Booking $booking): bool => BookingStatus::Cancelled !== $booking->getStatus(),
        ));
        foreach ($affected as $booking) {
            $booking->cancel($this->clock->now());
        }
        $this->sessions->save($session);
        $this->notifier->sessionCancelled($session, $affected);
        $this->availability->publish($session);
    }
}
