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
    #[Route(path: ['fr' => '/fr/mot-de-passe-oublie', 'en' => '/en/forgot-password'], name: 'forgot_password_request', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        UserRepositoryInterface $users,
        ResetPasswordHelperInterface $resetPassword,
        MessageBusInterface $bus,
    ): Response {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, ['label' => 'identity.form.email', 'constraints' => [new Assert\NotBlank(), new Assert\Email()]])
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
                        $user->getPreferredLocale()->value,
                        'email.reset.subject',
                        'email.reset.heading',
                        'email.reset.body',
                        [],
                        $this->generateUrl('reset_password', [
                            '_locale' => $user->getPreferredLocale()->value,
                            'token' => $token->getToken(),
                        ], UrlGeneratorInterface::ABSOLUTE_URL),
                        'email.reset.action',
                    ));
                } catch (ResetPasswordExceptionInterface) {
                }
            }

            return $this->redirectToRoute('forgot_password_check_email');
        }

        return $this->render('identity/request_reset.html.twig', ['form' => $form]);
    }

    #[Route(path: ['fr' => '/fr/mot-de-passe-oublie/email-envoye', 'en' => '/en/forgot-password/email-sent'], name: 'forgot_password_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('identity/check_email.html.twig');
    }

    #[Route(path: ['fr' => '/fr/nouveau-mot-de-passe/{token}', 'en' => '/en/reset-password/{token}'], name: 'reset_password', methods: ['GET', 'POST'])]
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
                'first_options' => ['label' => 'identity.reset.new_password'],
                'second_options' => ['label' => 'identity.reset.confirm_password'],
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
            $this->addFlash('success', 'identity.flash.password_changed');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('identity/reset_password.html.twig', ['form' => $form]);
    }
}
