<?php

declare(strict_types=1);

namespace App\Workshop\Application;

use App\Shared\Domain\Exception\BusinessRuleViolation;
use App\Workshop\Domain\Entity\Workshop;
use App\Workshop\Domain\Repository\WorkshopRepositoryInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Workflow\Registry;

final readonly class PublishWorkshopHandler
{
    public function __construct(
        private WorkshopRepositoryInterface $workshops,
        private Registry $workflows,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(Workshop $workshop): void
    {
        if (!$workshop->hasAllTranslations()) {
            throw new BusinessRuleViolation('workshop.error.translations_required');
        }

        if (!$workshop->hasFutureSession($this->clock->now())) {
            throw new BusinessRuleViolation('workshop.error.future_session_required');
        }

        $this->workflows->get($workshop, 'workshop')->apply($workshop, 'publish');
        $this->workshops->save($workshop);
    }
}
