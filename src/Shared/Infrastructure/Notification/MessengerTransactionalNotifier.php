<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Notification;

use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Enum\BookingStatus;
use App\Identity\Domain\Entity\OrganizerApplication;
use App\Identity\Domain\Enum\OrganizerApplicationStatus;
use App\Shared\Application\Message\SendTransactionalEmail;
use App\Shared\Application\Notification\TransactionalNotifierInterface;
use App\Workshop\Domain\Entity\WorkshopSession;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class MessengerTransactionalNotifier implements TransactionalNotifierInterface
{
    public function __construct(
        private MessageBusInterface $bus,
        private UrlGeneratorInterface $urls,
    ) {
    }

    public function bookingCreated(Booking $booking): void
    {
        $confirmed = BookingStatus::Confirmed === $booking->getStatus();
        $this->dispatch(
            $booking,
            $confirmed ? 'email.booking.confirmed.subject' : 'email.booking.waitlisted.subject',
            $confirmed ? 'email.booking.confirmed.heading' : 'email.booking.waitlisted.heading',
            $confirmed ? 'email.booking.confirmed.body' : 'email.booking.waitlisted.body',
        );
    }

    public function bookingPromoted(Booking $booking): void
    {
        $this->dispatch(
            $booking,
            'email.booking.promoted.subject',
            'email.booking.promoted.heading',
            'email.booking.promoted.body',
        );
    }

    public function bookingCancelled(Booking $booking): void
    {
        $this->dispatch(
            $booking,
            'email.booking.cancelled.subject',
            'email.booking.cancelled.heading',
            'email.booking.cancelled.body',
        );
    }

    public function bookingReminder(Booking $booking): void
    {
        $this->dispatch(
            $booking,
            'email.booking.reminder.subject',
            'email.booking.reminder.heading',
            'email.booking.reminder.body',
            date: $booking->getSession()->getStartsAt(),
        );
    }

    public function sessionCancelled(WorkshopSession $session, array $bookings): void
    {
        foreach ($bookings as $booking) {
            $this->dispatch(
                $booking,
                'email.session_cancelled.subject',
                'email.session_cancelled.heading',
                'email.session_cancelled.body',
                date: $session->getStartsAt(),
            );
        }
    }

    public function organizerApplicationReviewed(OrganizerApplication $application): void
    {
        $approved = OrganizerApplicationStatus::Approved === $application->getStatus();
        $user = $application->getApplicant();
        $locale = $user->getPreferredLocale()->value;
        $this->bus->dispatch(new SendTransactionalEmail(
            $user->getEmail(),
            $user->getDisplayName(),
            $locale,
            $approved ? 'email.organizer.approved.subject' : 'email.organizer.rejected.subject',
            $approved ? 'email.organizer.approved.heading' : 'email.organizer.rejected.heading',
            $approved ? 'email.organizer.approved.body' : 'email.organizer.rejected.body',
            [],
            $this->urls->generate($approved ? 'organizer_dashboard' : 'home', ['_locale' => $locale], UrlGeneratorInterface::ABSOLUTE_URL),
            'email.organizer.action',
        ));
    }

    /** @param array<string, scalar> $parameters */
    private function dispatch(
        Booking $booking,
        string $subjectKey,
        string $headingKey,
        string $bodyKey,
        array $parameters = [],
        ?\DateTimeImmutable $date = null,
    ): void {
        $attendee = $booking->getAttendee();
        $locale = $attendee->getPreferredLocale();
        $workshopTitle = $booking->getSession()->getWorkshop()->translation($locale)->getTitle();
        $this->bus->dispatch(new SendTransactionalEmail(
            $attendee->getEmail(),
            $attendee->getDisplayName(),
            $locale->value,
            $subjectKey,
            $headingKey,
            $bodyKey,
            ['workshop' => $workshopTitle, ...$parameters],
            $this->urls->generate('attendee_dashboard', ['_locale' => $locale->value], UrlGeneratorInterface::ABSOLUTE_URL),
            'email.booking.action',
            $date,
        ));
    }
}
