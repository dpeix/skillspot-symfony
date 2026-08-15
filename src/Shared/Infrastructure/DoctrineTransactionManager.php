<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Shared\Application\TransactionManagerInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTransactionManager implements TransactionManagerInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function run(\Closure $operation): mixed
    {
        return $this->entityManager->wrapInTransaction($operation);
    }
}
