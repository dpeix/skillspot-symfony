<?php

declare(strict_types=1);

namespace App\Shared\Application\Notification;

use App\Booking\Domain\Entity\Booking;
use App\Identity\Domain\Entity\OrganizerApplication;
use App\Workshop\Domain\Entity\WorkshopSession;

interface TransactionalNotifierInterface
{
    public function bookingCreated(Booking $booking): void;

    public function bookingPromoted(Booking $booking): void;

    public function bookingCancelled(Booking $booking): void;

    public function bookingReminder(Booking $booking): void;

    /** @param list<Booking> $bookings */
    public function sessionCancelled(WorkshopSession $session, array $bookings): void;

    public function organizerApplicationReviewed(OrganizerApplication $application): void;
}
