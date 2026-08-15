<?php

declare(strict_types=1);

namespace App\Identity\UI\Http;

use App\Identity\Application\Data\RegisterUserData;
use App\Identity\Application\RegisterUserHandler;
use App\Identity\Application\SendVerificationEmailHandler;
use App\Identity\Application\VerifyEmailHandler;
use App\Identity\UI\Form\RegistrationFormType;
use App\Shared\Domain\Enum\SupportedLocale;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

final class RegistrationController extends AbstractController
{
    #[Route(path: ['fr' => '/fr/inscription', 'en' => '/en/register'], name: 'register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        RegisterUserHandler $register,
        SendVerificationEmailHandler $sendVerification,
    ): Response {
        $data = new RegisterUserData();
        $data->preferredLocale = SupportedLocale::fromString($request->getLocale());
        $form = $this->createForm(RegistrationFormType::class, $data)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $register($data);
                $sendVerification($user);
                $this->addFlash('success', 'identity.flash.account_created');

                return $this->redirectToRoute('app_login');
            } catch (BusinessRuleViolation $exception) {
                $this->addFlash('error', ['key' => $exception->getTranslationKey(), 'parameters' => $exception->getParameters()]);
            }
        }

        return $this->render('identity/register.html.twig', ['form' => $form]);
    }

    #[Route(path: ['fr' => '/fr/confirmation-email/{id<\d+>}', 'en' => '/en/verify-email/{id<\d+>}'], name: 'verify_email', methods: ['GET'])]
    public function verify(int $id, Request $request, VerifyEmailHandler $verify): Response
    {
        try {
            $verify($id, $request);
            $this->addFlash('success', 'identity.flash.email_verified');
        } catch (BusinessRuleViolation $exception) {
            $this->addFlash('error', ['key' => $exception->getTranslationKey(), 'parameters' => $exception->getParameters()]);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_login');
    }
}
