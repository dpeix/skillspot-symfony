<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class HealthController
{
    #[Route('/healthz', name: 'health', methods: ['GET'])]
    public function __invoke(Connection $connection): JsonResponse
    {
        try {
            $connection->executeQuery('SELECT 1')->fetchOne();

            return new JsonResponse(['status' => 'ok']);
        } catch (\Throwable) {
            return new JsonResponse(['status' => 'unavailable'], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
