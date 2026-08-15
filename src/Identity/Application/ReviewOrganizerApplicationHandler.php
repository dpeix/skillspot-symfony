<?php

declare(strict_types=1);

namespace App\Identity\Application;

use App\Identity\Domain\Entity\OrganizerApplication;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Repository\OrganizerApplicationRepositoryInterface;
use App\Shared\Application\Notification\TransactionalNotifierInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Workflow\Registry;

final readonly class ReviewOrganizerApplicationHandler
{
    public function __construct(
        private OrganizerApplicationRepositoryInterface $applications,
        private Registry $workflows,
        private TransactionalNotifierInterface $notifier,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(OrganizerApplication $application, User $reviewer, bool $approve, ?string $note = null): void
    {
        $transition = $approve ? 'approve' : 'reject';
        $application->recordDecision($reviewer, $note, $this->clock->now());
        $this->workflows->get($application, 'organizer_application')->apply($application, $transition);
        if ($approve) {
            $application->finalizeApproval();
        }
        $this->applications->save($application);
        $this->notifier->organizerApplicationReviewed($application);
    }
}
