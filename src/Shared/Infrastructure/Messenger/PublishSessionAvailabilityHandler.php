<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger;

use App\Shared\Application\Message\PublishSessionAvailability;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class PublishSessionAvailabilityHandler
{
    public function __construct(private HubInterface $hub)
    {
    }

    public function __invoke(PublishSessionAvailability $message): void
    {
        $this->hub->publish(new Update(
            '/sessions/'.$message->sessionId.'/availability',
            json_encode([
                'sessionId' => $message->sessionId,
                'remainingSeats' => $message->remainingSeats,
                'status' => $message->status,
            ], \JSON_THROW_ON_ERROR),
        ));
    }
}
