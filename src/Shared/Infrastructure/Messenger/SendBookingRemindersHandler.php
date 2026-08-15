<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Shared\Application\Message\SendBookingReminders;
use App\Shared\Application\Notification\TransactionalNotifierInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SendBookingRemindersHandler
{
    public function __construct(
        private BookingRepositoryInterface $bookings,
        private TransactionalNotifierInterface $notifier,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(SendBookingReminders $message): void
    {
        $now = $this->clock->now();
        foreach ($this->bookings->remindersDue($now->modify('+23 hours 50 minutes'), $now->modify('+24 hours 10 minutes')) as $booking) {
            $this->notifier->bookingReminder($booking);
            $booking->markReminderSent($now);
            $this->bookings->save($booking);
        }
    }
}
