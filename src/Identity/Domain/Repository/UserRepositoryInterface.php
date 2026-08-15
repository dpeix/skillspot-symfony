<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Entity\User;

interface UserRepositoryInterface
{
    public function get(int $id): ?User;

    public function findByEmail(string $email): ?User;

    public function save(User $user): void;
}
