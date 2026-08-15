<?php

declare(strict_types=1);

namespace App\Workshop\UI\Api;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use App\Shared\Domain\Enum\SupportedLocale;
use App\Workshop\Domain\Repository\WorkshopRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;

/** @implements ProviderInterface<WorkshopResource> */
final readonly class WorkshopResourceProvider implements ProviderInterface
{
    public function __construct(
        private WorkshopRepositoryInterface $workshops,
        private ResourceFactory $resources,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!$operation instanceof GetCollection) {
            $identifier = $uriVariables['slug'] ?? '';
            $workshop = $this->workshops->findPublishedBySlug(
                SupportedLocale::French,
                \is_string($identifier) ? $identifier : '',
            );

            return $workshop ? $this->resources->workshop($workshop) : null;
        }

        $request = $context['request'] ?? null;
        $filters = $request instanceof Request ? array_filter([
            'category' => $request->query->getString('category'),
            'level' => $request->query->getString('level'),
            'mode' => $request->query->getString('mode'),
            'date' => $request->query->getString('date'),
        ]) : [];
        $items = [];
        foreach ($this->workshops->searchPublished($filters, 100) as $workshop) {
            $items[] = $this->resources->workshop($workshop);
        }
        $page = $request instanceof Request ? max(1, $request->query->getInt('page', 1)) : 1;
        $perPage = $request instanceof Request ? min(24, max(1, $request->query->getInt('itemsPerPage', 12))) : 12;

        return new ArrayPaginator($items, ($page - 1) * $perPage, $perPage);
    }
}
