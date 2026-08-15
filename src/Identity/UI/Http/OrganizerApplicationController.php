<?php

declare(strict_types=1);

namespace App\Identity\UI\Http;

use App\Identity\Application\ApplyForOrganizerHandler;
use App\Identity\Application\Data\OrganizerApplicationData;
use App\Identity\Domain\Entity\User;
use App\Identity\UI\Form\OrganizerApplicationType;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrganizerApplicationController extends AbstractController
{
    #[Route(path: ['fr' => '/fr/organisateur/devenir-organisateur', 'en' => '/en/organizer/apply'], name: 'organizer_apply', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, ApplyForOrganizerHandler $apply): Response
    {
        if ($this->isGranted('ROLE_ORGANIZER')) {
            return $this->redirectToRoute('organizer_dashboard');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $data = new OrganizerApplicationData();
        $form = $this->createForm(OrganizerApplicationType::class, $data)->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $apply($user, $data);
                $this->addFlash('success', 'organizer.application.flash.sent');

                return $this->redirectToRoute('attendee_dashboard');
            } catch (BusinessRuleViolation $exception) {
                $this->addFlash('error', ['key' => $exception->getTranslationKey(), 'parameters' => $exception->getParameters()]);
            }
        }

        return $this->render('identity/organizer_apply.html.twig', ['form' => $form]);
    }
}
