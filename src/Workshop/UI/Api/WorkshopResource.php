<?php

declare(strict_types=1);

namespace App\Workshop\UI\Api;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;

#[ApiResource(
    shortName: 'Workshop',
    operations: [
        new GetCollection(
            uriTemplate: '/workshops',
            paginationEnabled: true,
            parameters: [
                'category' => new QueryParameter(description: 'Filter by category.', schema: ['type' => 'string', 'enum' => ['development', 'design', 'data', 'product', 'career']]),
                'level' => new QueryParameter(description: 'Filter by experience level.', schema: ['type' => 'string', 'enum' => ['beginner', 'intermediate', 'advanced']]),
                'mode' => new QueryParameter(description: 'Filter by delivery mode.', schema: ['type' => 'string', 'enum' => ['onsite', 'online']]),
                'date' => new QueryParameter(description: 'Filter by session date in Europe/Paris.', schema: ['type' => 'string', 'format' => 'date']),
            ],
            provider: WorkshopResourceProvider::class,
        ),
        new Get(uriTemplate: '/workshops/{slug}', provider: WorkshopResourceProvider::class),
    ],
)]
final readonly class WorkshopResource
{
    /** @param list<SessionResource> $sessions */
    public function __construct(
        #[ApiProperty(identifier: true)]
        public string $slug,
        public string $title,
        public string $description,
        public string $category,
        public string $level,
        public string $organizer,
        public array $sessions,
    ) {
    }
}
