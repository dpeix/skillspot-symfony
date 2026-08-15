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
            $confirmed ? 'Réservation confirmée' : 'Vous êtes sur liste d’attente',
            $confirmed ? 'Votre place est réservée' : 'Votre demande est enregistrée',
            $confirmed
                ? 'Votre place pour « '.$booking->getSession()->getWorkshop()->getTitle().' » est confirmée.'
                : 'La session est complète. Nous vous préviendrons automatiquement si une place se libère.',
        );
    }

    public function bookingPromoted(Booking $booking): void
    {
        $this->dispatch(
            $booking,
            'Une place vient de se libérer',
            'Votre réservation est maintenant confirmée',
            'Bonne nouvelle : vous participerez à « '.$booking->getSession()->getWorkshop()->getTitle().' ».',
        );
    }

    public function bookingCancelled(Booking $booking): void
    {
        $this->dispatch(
            $booking,
            'Réservation annulée',
            'Votre annulation est enregistrée',
            'Votre réservation pour « '.$booking->getSession()->getWorkshop()->getTitle().' » a bien été annulée.',
        );
    }

    public function bookingReminder(Booking $booking): void
    {
        $this->dispatch(
            $booking,
            'Votre atelier commence demain',
            'Rendez-vous dans 24 heures',
            '« '.$booking->getSession()->getWorkshop()->getTitle().' » commence le '.$booking->getSession()->getStartsAt()->setTimezone(new \DateTimeZone('Europe/Paris'))->format('d/m/Y à H:i').'.',
        );
    }

    public function sessionCancelled(WorkshopSession $session, array $bookings): void
    {
        foreach ($bookings as $booking) {
            $this->dispatch(
                $booking,
                'Session annulée',
                'La session ne pourra pas avoir lieu',
                'La session de « '.$session->getWorkshop()->getTitle().' » prévue le '.$session->getStartsAt()->setTimezone(new \DateTimeZone('Europe/Paris'))->format('d/m/Y').' est annulée.',
            );
        }
    }

    public function organizerApplicationReviewed(OrganizerApplication $application): void
    {
        $approved = OrganizerApplicationStatus::Approved === $application->getStatus();
        $user = $application->getApplicant();
        $this->bus->dispatch(new SendTransactionalEmail(
            $user->getEmail(),
            $user->getDisplayName(),
            $approved ? 'Votre demande organisateur est acceptée' : 'Décision concernant votre demande organisateur',
            $approved ? 'Bienvenue parmi les organisateurs' : 'Votre demande n’a pas été retenue',
            $approved
                ? 'Vous pouvez désormais créer et publier vos ateliers sur SkillSpot.'
                : 'Vous pourrez soumettre une nouvelle demande après avoir complété votre projet.',
            $this->urls->generate($approved ? 'organizer_dashboard' : 'home', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'Accéder à SkillSpot',
        ));
    }

    private function dispatch(Booking $booking, string $subject, string $heading, string $body): void
    {
        $attendee = $booking->getAttendee();
        $this->bus->dispatch(new SendTransactionalEmail(
            $attendee->getEmail(),
            $attendee->getDisplayName(),
            $subject,
            $heading,
            $body,
            $this->urls->generate('attendee_dashboard', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'Voir mes réservations',
        ));
    }
}
