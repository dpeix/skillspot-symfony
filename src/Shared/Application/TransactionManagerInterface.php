<?php

declare(strict_types=1);

namespace App\Shared\Application;

interface TransactionManagerInterface
{
    /**
     * @template T
     *
     * @param \Closure(): T $operation
     *
     * @return T
     */
    public function run(\Closure $operation): mixed;
}
