<?php

declare(strict_types=1);

namespace App\Identity\UI\Http;

use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Application\Message\SendTransactionalEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final class ResetPasswordController extends AbstractController
{
    #[Route('/mot-de-passe-oublie', name: 'forgot_password_request', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        UserRepositoryInterface $users,
        ResetPasswordHelperInterface $resetPassword,
        MessageBusInterface $bus,
    ): Response {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, ['label' => 'Adresse e-mail', 'constraints' => [new Assert\NotBlank(), new Assert\Email()]])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            if (!\is_string($email)) {
                throw new \LogicException('The reset e-mail must be a string.');
            }
            $user = $users->findByEmail($email);
            if ($user) {
                try {
                    $token = $resetPassword->generateResetToken($user);
                    $bus->dispatch(new SendTransactionalEmail(
                        $user->getEmail(),
                        $user->getDisplayName(),
                        'Réinitialisez votre mot de passe',
                        'Vous avez demandé un nouveau mot de passe',
                        'Ce lien est temporaire. Ignorez cet e-mail si vous n’êtes pas à l’origine de la demande.',
                        $this->generateUrl('reset_password', ['token' => $token->getToken()], UrlGeneratorInterface::ABSOLUTE_URL),
                        'Choisir un mot de passe',
                    ));
                } catch (ResetPasswordExceptionInterface) {
                }
            }

            return $this->redirectToRoute('forgot_password_check_email');
        }

        return $this->render('identity/request_reset.html.twig', ['form' => $form]);
    }

    #[Route('/mot-de-passe-oublie/email-envoye', name: 'forgot_password_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('identity/check_email.html.twig');
    }

    #[Route('/nouveau-mot-de-passe/{token}', name: 'reset_password', methods: ['GET', 'POST'])]
    public function reset(
        string $token,
        Request $request,
        ResetPasswordHelperInterface $resetPassword,
        UserPasswordHasherInterface $hasher,
        UserRepositoryInterface $users,
    ): Response {
        try {
            $user = $resetPassword->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $exception) {
            $this->addFlash('error', $exception->getReason());

            return $this->redirectToRoute('forgot_password_request');
        }
        if (!$user instanceof User) {
            throw $this->createNotFoundException();
        }

        $form = $this->createFormBuilder()
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => ['label' => 'Nouveau mot de passe'],
                'second_options' => ['label' => 'Confirmer le mot de passe'],
                'constraints' => [new Assert\Length(min: 10)],
            ])
            ->getForm()
            ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if (!\is_string($plainPassword)) {
                throw new \LogicException('The password must be a string.');
            }
            $user->changePassword($hasher->hashPassword($user, $plainPassword));
            $users->save($user);
            $resetPassword->removeResetRequest($token);
            $this->addFlash('success', 'Votre mot de passe a été modifié.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('identity/reset_password.html.twig', ['form' => $form]);
    }
}
