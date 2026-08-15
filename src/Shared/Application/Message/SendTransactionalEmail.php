<?php

declare(strict_types=1);

namespace App\Shared\Application\Message;

final readonly class SendTransactionalEmail
{
    public function __construct(
        public string $to,
        public string $recipientName,
        public string $subject,
        public string $heading,
        public string $body,
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
    ) {
    }
}
