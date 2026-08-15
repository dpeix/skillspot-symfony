<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\Entity\User;
use App\Shared\Application\Message\SendTransactionalEmail;
use Symfony\Component\Messenger\MessageBusInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

final readonly class SendVerificationEmailHandler
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmail,
        private MessageBusInterface $bus,
    ) {
    }

    public function __invoke(User $user): void
    {
        $signature = $this->verifyEmail->generateSignature(
            'verify_email',
            (string) $user->getId(),
            $user->getEmail(),
            ['id' => $user->getId(), '_locale' => $user->getPreferredLocale()->value],
        );

        $this->bus->dispatch(new SendTransactionalEmail(
            $user->getEmail(),
            $user->getDisplayName(),
            $user->getPreferredLocale()->value,
            'email.verification.subject',
            'email.verification.heading',
            'email.verification.body',
            [],
            $signature->getSignedUrl(),
            'email.verification.action',
        ));
    }
}
