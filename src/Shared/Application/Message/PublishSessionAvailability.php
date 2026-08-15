<?php

declare(strict_types=1);

namespace App\Shared\Application\Message;

final readonly class PublishSessionAvailability
{
    public function __construct(
        public int $sessionId,
        public int $remainingSeats,
        public string $status,
    ) {
    }
}
