<?php

declare(strict_types=1);

namespace App\Workshop\UI\Api;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource(
    shortName: 'Session',
    operations: [new Get(uriTemplate: '/sessions/{id}', provider: SessionResourceProvider::class)],
)]
final readonly class SessionResource
{
    public function __construct(
        #[ApiProperty(identifier: true)]
        public int $id,
        public string $workshopSlug,
        public string $workshopTitle,
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
        public int $capacity,
        public int $remainingSeats,
        public string $mode,
        public ?string $location,
        public string $status,
    ) {
    }
}
