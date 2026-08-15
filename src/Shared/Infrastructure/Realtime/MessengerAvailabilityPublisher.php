<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Realtime;

use App\Shared\Application\Message\PublishSessionAvailability;
use App\Shared\Application\Realtime\AvailabilityPublisherInterface;
use App\Workshop\Domain\Entity\WorkshopSession;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class MessengerAvailabilityPublisher implements AvailabilityPublisherInterface
{
    public function __construct(private MessageBusInterface $bus)
    {
    }

    public function publish(WorkshopSession $session): void
    {
        $this->bus->dispatch(new PublishSessionAvailability(
            (int) $session->getId(),
            $session->remainingSeats(),
            $session->getStatus()->value,
        ));
    }
}
