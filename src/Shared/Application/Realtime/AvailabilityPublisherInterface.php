<?php

declare(strict_types=1);

namespace App\Shared\Application\Realtime;

use App\Workshop\Domain\Entity\WorkshopSession;

interface AvailabilityPublisherInterface
{
    public function publish(WorkshopSession $session): void;
}
