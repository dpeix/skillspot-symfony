<?php

declare(strict_types=1);

namespace App\Workshop\UI\Http;

use App\Booking\Application\MarkAttendanceHandler;
use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Enum\AttendanceStatus;
use App\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Identity\Domain\Entity\User;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use App\Workshop\Application\AddSessionHandler;
use App\Workshop\Application\CancelSessionHandler;
use App\Workshop\Application\CreateWorkshopHandler;
use App\Workshop\Application\Data\SessionData;
use App\Workshop\Application\Data\WorkshopData;
use App\Workshop\Application\PublishWorkshopHandler;
use App\Workshop\Application\UpdateWorkshopHandler;
use App\Workshop\Domain\Entity\Workshop;
use App\Workshop\Domain\Entity\WorkshopSession;
use App\Workshop\Domain\Repository\WorkshopRepositoryInterface;
use App\Workshop\UI\Form\SessionType;
use App\Workshop\UI\Form\WorkshopType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrganizerController extends AbstractController
{
    #[Route(path: ['fr' => '/fr/organisateur', 'en' => '/en/organizer'], name: 'organizer_dashboard', methods: ['GET'])]
    public function dashboard(
        WorkshopRepositoryInterface $workshops,
        BookingRepositoryInterface $bookings,
    ): Response {
        $user = $this->organizer();

        return $this->render('organizer/dashboard.html.twig', [
            'workshops' => $workshops->ownedBy($user),
            'stats' => $bookings->statisticsForOrganizer($user),
        ]);
    }

    #[Route(path: ['fr' => '/fr/organisateur/ateliers/nouveau', 'en' => '/en/organizer/workshops/new'], name: 'organizer_workshop_new', methods: ['GET', 'POST'])]
    public function newWorkshop(Request $request, CreateWorkshopHandler $create): Response
    {
        $data = new WorkshopData();
        $form = $this->createForm(WorkshopType::class, $data)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $workshop = $create($this->organizer(), $data);
                $this->addFlash('success', 'workshop.flash.created');

                return $this->redirectToRoute('organizer_session_new', ['id' => $workshop->getId()]);
            } catch (BusinessRuleViolation $exception) {
                $this->addFlash('error', ['key' => $exception->getTranslationKey(), 'parameters' => $exception->getParameters()]);
            }
        }

        return $this->render('organizer/workshop_form.html.twig', ['form' => $form, 'title' => 'workshop.form.create_title']);
    }

    #[Route(path: ['fr' => '/fr/organisateur/ateliers/{id<\d+>}/modifier', 'en' => '/en/organizer/workshops/{id<\d+>}/edit'], name: 'organizer_workshop_edit', methods: ['GET', 'POST'])]
    public function editWorkshop(Workshop $workshop, Request $request, UpdateWorkshopHandler $update): Response
    {
        $this->denyAccessUnlessGranted('WORKSHOP_MANAGE', $workshop);
        $data = new WorkshopData();
        $data->titleFr = $workshop->translation('fr')->getTitle();
        $data->descriptionFr = $workshop->translation('fr')->getDescription();
        $data->titleEn = $workshop->translation('en')->getTitle();
        $data->descriptionEn = $workshop->translation('en')->getDescription();
        $data->category = $workshop->getCategory();
        $data->level = $workshop->getLevel();
        $form = $this->createForm(WorkshopType::class, $data)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $update($workshop, $data);
                $this->addFlash('success', 'workshop.flash.updated');

                return $this->redirectToRoute('organizer_dashboard');
            } catch (BusinessRuleViolation $exception) {
                $this->addFlash('error', ['key' => $exception->getTranslationKey(), 'parameters' => $exception->getParameters()]);
            }
        }

        return $this->render('organizer/workshop_form.html.twig', ['form' => $form, 'title' => 'workshop.form.edit_title']);
    }

    #[Route(path: ['fr' => '/fr/organisateur/ateliers/{id<\d+>}/sessions/nouvelle', 'en' => '/en/organizer/workshops/{id<\d+>}/sessions/new'], name: 'organizer_session_new', methods: ['GET', 'POST'])]
    public function newSession(Workshop $workshop, Request $request, AddSessionHandler $add): Response
    {
        $this->denyAccessUnlessGranted('WORKSHOP_MANAGE', $workshop);
        $data = new SessionData();
        $data->startsAt = new \DateTimeImmutable('tomorrow 18:00', new \DateTimeZone('Europe/Paris'));
        $data->endsAt = $data->startsAt->modify('+2 hours');
        $form = $this->createForm(SessionType::class, $data)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $add($workshop, $data);
                $this->addFlash('success', 'session.flash.created');

                return $this->redirectToRoute('organizer_dashboard');
            } catch (BusinessRuleViolation $exception) {
                $this->addFlash('error', ['key' => $exception->getTranslationKey(), 'parameters' => $exception->getParameters()]);
            }
        }

        return $this->render('organizer/session_form.html.twig', ['form' => $form, 'workshop' => $workshop]);
    }

    #[Route(path: ['fr' => '/fr/organisateur/ateliers/{id<\d+>}/publier', 'en' => '/en/organizer/workshops/{id<\d+>}/publish'], name: 'organizer_workshop_publish', methods: ['POST'])]
    public function publish(Workshop $workshop, Request $request, PublishWorkshopHandler $publish): Response
    {
        $this->denyAccessUnlessGranted('WORKSHOP_MANAGE', $workshop);
        if (!$this->isCsrfTokenValid('publish-'.$workshop->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $publish($workshop);
            $this->addFlash('success', 'workshop.flash.published');
        } catch (BusinessRuleViolation $exception) {
            $this->addFlash('error', ['key' => $exception->getTranslationKey(), 'parameters' => $exception->getParameters()]);
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('organizer_dashboard');
    }

    #[Route(path: ['fr' => '/fr/organisateur/sessions/{id<\d+>}/participants', 'en' => '/en/organizer/sessions/{id<\d+>}/attendees'], name: 'organizer_session_participants', methods: ['GET'])]
    public function participants(WorkshopSession $session, BookingRepositoryInterface $bookings): Response
    {
        $this->denyAccessUnlessGranted('WORKSHOP_MANAGE', $session->getWorkshop());

        return $this->render('organizer/participants.html.twig', [
            'session' => $session,
            'bookings' => $bookings->forSession($session),
        ]);
    }

    #[Route(path: ['fr' => '/fr/organisateur/sessions/{id<\d+>}/annuler', 'en' => '/en/organizer/sessions/{id<\d+>}/cancel'], name: 'organizer_session_cancel', methods: ['POST'])]
    public function cancelSession(WorkshopSession $session, Request $request, CancelSessionHandler $cancel): Response
    {
        $this->denyAccessUnlessGranted('WORKSHOP_MANAGE', $session->getWorkshop());
        if (!$this->isCsrfTokenValid('cancel-session-'.$session->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $cancel($session, $this->organizer());
            $this->addFlash('success', 'session.flash.cancelled');
        } catch (BusinessRuleViolation $exception) {
            $this->addFlash('error', ['key' => $exception->getTranslationKey(), 'parameters' => $exception->getParameters()]);
        }

        return $this->redirectToRoute('organizer_dashboard');
    }

    #[Route(path: ['fr' => '/fr/organisateur/reservations/{id<\d+>}/presence/{attendance}', 'en' => '/en/organizer/bookings/{id<\d+>}/attendance/{attendance}'], name: 'organizer_attendance', requirements: ['attendance' => 'attended|no_show'], methods: ['POST'])]
    public function attendance(
        Booking $booking,
        string $attendance,
        Request $request,
        MarkAttendanceHandler $mark,
    ): Response {
        $this->denyAccessUnlessGranted('WORKSHOP_MANAGE', $booking->getSession()->getWorkshop());
        if (!$this->isCsrfTokenValid('attendance-'.$booking->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $mark($booking, $this->organizer(), AttendanceStatus::from($attendance));
            $this->addFlash('success', 'attendance.flash.updated');
        } catch (BusinessRuleViolation $exception) {
            $this->addFlash('error', ['key' => $exception->getTranslationKey(), 'parameters' => $exception->getParameters()]);
        }

        return $this->redirectToRoute('organizer_session_participants', ['id' => $booking->getSession()->getId()]);
    }

    private function organizer(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
