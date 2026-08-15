<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use Symfony\Component\HttpFoundation\Request;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

final readonly class VerifyEmailHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private VerifyEmailHelperInterface $verifyEmail,
    ) {
    }

    public function __invoke(int $userId, Request $request): void
    {
        $user = $this->users->get($userId);
        if (!$user) {
            throw new BusinessRuleViolation('Ce lien de confirmation est invalide.');
        }

        $this->verifyEmail->validateEmailConfirmationFromRequest($request, (string) $userId, $user->getEmail());
        $user->verify();
        $this->users->save($user);
    }
}
