<?php

declare(strict_types=1);

namespace App\Booking\UI\Http;

use App\Booking\Application\BookSessionHandler;
use App\Booking\Application\CancelBookingHandler;
use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Identity\Domain\Entity\User;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BookingController extends AbstractController
{
    #[Route('/sessions/{id<\d+>}/reservation', name: 'booking_create', methods: ['POST'])]
    public function create(int $id, Request $request, BookSessionHandler $book): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if (!$this->isCsrfTokenValid('book-'.$id, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        try {
            $booking = $book($id, $user);
            $this->addFlash('success', 'waitlisted' === $booking->getStatus()->value
                ? 'La session est complète : vous êtes sur liste d’attente.'
                : 'Votre place est confirmée.');
        } catch (BusinessRuleViolation $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('attendee_dashboard');
    }

    #[Route('/dashboard', name: 'attendee_dashboard', methods: ['GET'])]
    public function dashboard(BookingRepositoryInterface $bookings): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('booking/dashboard.html.twig', ['bookings' => $bookings->forUser($user)]);
    }

    #[Route('/dashboard/reservations/{id<\d+>}/annulation', name: 'booking_cancel', methods: ['POST'])]
    public function cancel(int $id, Request $request, CancelBookingHandler $cancel): Response
    {
        if (!$this->isCsrfTokenValid('cancel-booking-'.$id, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        try {
            $cancel($id, $user);
            $this->addFlash('success', 'Votre réservation est annulée.');
        } catch (BusinessRuleViolation $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('attendee_dashboard');
    }
}
