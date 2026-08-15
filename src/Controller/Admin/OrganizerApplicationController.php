<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Identity\Application\ReviewOrganizerApplicationHandler;
use App\Identity\Domain\Entity\OrganizerApplication;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\OrganizerApplicationRepositoryInterface;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrganizerApplicationController extends AbstractController
{
    #[Route('/admin/organizer-applications', name: 'admin_organizer_applications', methods: ['GET'])]
    public function index(OrganizerApplicationRepositoryInterface $applications): Response
    {
        return $this->render('admin/organizer_applications.html.twig', ['applications' => $applications->pending()]);
    }

    #[Route('/admin/organizer-applications/{id<\d+>}/{decision}', name: 'admin_organizer_application_review', requirements: ['decision' => 'approve|reject'], methods: ['POST'])]
    public function review(
        OrganizerApplication $application,
        string $decision,
        Request $request,
        ReviewOrganizerApplicationHandler $review,
    ): Response {
        if (!$this->isCsrfTokenValid('review-'.$application->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }
        $admin = $this->getUser();
        if (!$admin instanceof User) {
            throw $this->createAccessDeniedException();
        }

        try {
            $review($application, $admin, 'approve' === $decision, $request->request->getString('note'));
            $this->addFlash('success', 'La demande a été traitée.');
        } catch (BusinessRuleViolation $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_organizer_applications');
    }
}
