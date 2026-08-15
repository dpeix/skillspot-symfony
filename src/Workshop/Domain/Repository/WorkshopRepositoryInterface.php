<?php

declare(strict_types=1);

namespace App\Workshop\Domain\Repository;

use App\Identity\Domain\Entity\User;
use App\Shared\Domain\Enum\SupportedLocale;
use App\Workshop\Domain\Entity\Workshop;

interface WorkshopRepositoryInterface
{
    public function get(int $id): ?Workshop;

    /**
     * @param array<string, string> $filters
     *
     * @return list<Workshop>
     */
    public function searchPublished(array $filters = [], int $limit = 24): array;

    /** @return list<Workshop> */
    public function ownedBy(User $owner): array;

    public function findPublishedBySlug(SupportedLocale $locale, string $slug): ?Workshop;

    public function findPublishedByAnySlug(string $slug): ?Workshop;

    public function save(Workshop $workshop): void;

    public function nextAvailableSlug(SupportedLocale $locale, string $baseSlug): string;
}
