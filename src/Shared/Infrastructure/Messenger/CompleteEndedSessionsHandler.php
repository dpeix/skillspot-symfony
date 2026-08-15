<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Shared\Application\Message\CompleteEndedSessions;
use App\Workshop\Domain\Repository\WorkshopSessionRepositoryInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CompleteEndedSessionsHandler
{
    public function __construct(
        private WorkshopSessionRepositoryInterface $sessions,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(CompleteEndedSessions $message): void
    {
        $now = $this->clock->now();
        foreach ($this->sessions->endedBefore($now) as $session) {
            $session->complete($now);
            $this->sessions->save($session);
        }
    }
}
