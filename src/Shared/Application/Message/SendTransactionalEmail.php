<?php

declare(strict_types=1);

namespace App\Shared\Application\Message;

final readonly class SendTransactionalEmail
{
    public function __construct(
        public string $to,
        public string $recipientName,
        public string $locale,
        public string $subjectKey,
        public string $headingKey,
        public string $bodyKey,
        /** @var array<string, scalar> */
        public array $parameters = [],
        public ?string $actionUrl = null,
        public ?string $actionLabelKey = null,
        public ?\DateTimeImmutable $date = null,
    ) {
    }
}
