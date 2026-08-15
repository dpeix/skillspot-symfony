<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Entity\OrganizerApplication;
use App\Identity\Domain\Entity\User;

interface OrganizerApplicationRepositoryInterface
{
    public function hasPendingFor(User $user): bool;

    /** @return list<OrganizerApplication> */
    public function pending(): array;

    public function save(OrganizerApplication $application): void;
}
