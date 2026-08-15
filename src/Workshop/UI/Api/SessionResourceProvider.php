<?php

declare(strict_types=1);

namespace App\Workshop\UI\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Workshop\Domain\Enum\WorkshopStatus;
use App\Workshop\Domain\Repository\WorkshopSessionRepositoryInterface;

/** @implements ProviderInterface<SessionResource> */
final readonly class SessionResourceProvider implements ProviderInterface
{
    public function __construct(
        private WorkshopSessionRepositoryInterface $sessions,
        private ResourceFactory $resources,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
    {
        $identifier = $uriVariables['id'] ?? 0;
        $session = $this->sessions->get(is_numeric($identifier) ? (int) $identifier : 0);

        return $session && WorkshopStatus::Published === $session->getWorkshop()->getStatus()
            ? $this->resources->session($session)
            : null;
    }
}
