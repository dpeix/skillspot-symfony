<?php

declare(strict_types=1);

namespace App\Workshop\UI\Api;

use App\Shared\Domain\Enum\SupportedLocale;
use App\Workshop\Domain\Entity\Workshop;
use App\Workshop\Domain\Entity\WorkshopSession;

final readonly class ResourceFactory
{
    public function session(WorkshopSession $session): SessionResource
    {
        $workshop = $session->getWorkshop();
        $french = $workshop->translation(SupportedLocale::French);

        return new SessionResource(
            (int) $session->getId(),
            $french->getSlug(),
            $french->getTitle(),
            $this->translations($workshop),
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

        $french = $workshop->translation(SupportedLocale::French);

        return new WorkshopResource(
            $french->getSlug(),
            $french->getTitle(),
            $french->getDescription(),
            $this->translations($workshop),
            $workshop->getCategory()->value,
            $workshop->getLevel()->value,
            $workshop->getOwner()->getDisplayName(),
            $sessions,
        );
    }

    /** @return array{fr: array{slug: string, title: string, description: string}, en: array{slug: string, title: string, description: string}} */
    private function translations(Workshop $workshop): array
    {
        $french = $workshop->translation(SupportedLocale::French);
        $english = $workshop->translation(SupportedLocale::English);

        return [
            'fr' => [
                'slug' => $french->getSlug(),
                'title' => $french->getTitle(),
                'description' => $french->getDescription(),
            ],
            'en' => [
                'slug' => $english->getSlug(),
                'title' => $english->getTitle(),
                'description' => $english->getDescription(),
            ],
        ];
    }
}
