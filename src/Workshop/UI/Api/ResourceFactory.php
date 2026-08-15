<?php

declare(strict_types=1);

namespace App\Workshop\UI\Api;

use App\Workshop\Domain\Entity\Workshop;
use App\Workshop\Domain\Entity\WorkshopSession;

final readonly class ResourceFactory
{
    public function session(WorkshopSession $session): SessionResource
    {
        return new SessionResource(
            (int) $session->getId(),
            $session->getWorkshop()->getSlug(),
            $session->getWorkshop()->getTitle(),
            $session->getStartsAt(),
            $session->getEndsAt(),
            $session->getCapacity(),
            $session->remainingSeats(),
            $session->getMode()->value,
            $session->getLocation(),
            $session->getStatus()->value,
        );
    }

    public function workshop(Workshop $workshop): WorkshopResource
    {
        $sessions = [];
        foreach ($workshop->getSessions() as $session) {
            $sessions[] = $this->session($session);
        }

        return new WorkshopResource(
            $workshop->getSlug(),
            $workshop->getTitle(),
            $workshop->getDescription(),
            $workshop->getCategory()->value,
            $workshop->getLevel()->value,
            $workshop->getOwner()->getDisplayName(),
            $sessions,
        );
    }
}
