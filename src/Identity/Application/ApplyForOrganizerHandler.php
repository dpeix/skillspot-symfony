<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Application\Data\OrganizerApplicationData;
use App\Identity\Domain\Entity\OrganizerApplication;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\OrganizerApplicationRepositoryInterface;
use App\Shared\Domain\Exception\BusinessRuleViolation;

final readonly class ApplyForOrganizerHandler
{
    public function __construct(private OrganizerApplicationRepositoryInterface $applications)
    {
    }

    public function __invoke(User $user, OrganizerApplicationData $data): OrganizerApplication
    {
        if ($this->applications->hasPendingFor($user)) {
            throw new BusinessRuleViolation('organizer.application.error.already_pending');
        }

        $application = new OrganizerApplication($user, $data->motivation);
        $this->applications->save($application);

        return $application;
    }
}
