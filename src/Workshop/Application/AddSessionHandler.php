<?php

declare(strict_types=1);

namespace App\Workshop\Application;

use App\Shared\Domain\Exception\BusinessRuleViolation;
use App\Workshop\Application\Data\SessionData;
use App\Workshop\Domain\Entity\Workshop;
use App\Workshop\Domain\Entity\WorkshopSession;
use App\Workshop\Domain\Repository\WorkshopSessionRepositoryInterface;

final readonly class AddSessionHandler
{
    public function __construct(private WorkshopSessionRepositoryInterface $sessions)
    {
    }

    public function __invoke(Workshop $workshop, SessionData $data): WorkshopSession
    {
        if (!$data->startsAt || !$data->endsAt) {
            throw new BusinessRuleViolation('session.error.schedule_required');
        }

        $session = new WorkshopSession(
            $workshop,
            $data->startsAt,
            $data->endsAt,
            $data->capacity,
            $data->mode,
            $data->location,
            $data->meetingUrl,
        );
        $this->sessions->save($session);

        return $session;
    }
}
