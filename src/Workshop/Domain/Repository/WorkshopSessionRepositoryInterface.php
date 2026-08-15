<?php

declare(strict_types=1);

namespace App\Workshop\Domain\Repository;

use App\Workshop\Domain\Entity\WorkshopSession;

interface WorkshopSessionRepositoryInterface
{
    public function get(int $id): ?WorkshopSession;

    public function lock(int $id): WorkshopSession;

    /** @return list<WorkshopSession> */
    public function endedBefore(\DateTimeImmutable $now): array;

    public function save(WorkshopSession $session): void;
}
