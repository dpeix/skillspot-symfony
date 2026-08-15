<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Data\RegisterUserData;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepositoryInterface $users,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(RegisterUserData $data): User
    {
        if ($this->users->findByEmail($data->email)) {
            throw new BusinessRuleViolation('identity.error.email_already_used');
        }

        $user = new User($data->email, $data->firstName, $data->lastName, preferredLocale: $data->preferredLocale);
        $user->changePassword($this->passwordHasher->hashPassword($user, $data->plainPassword));
        $this->users->save($user);

        return $user;
    }
}
