<?php

declare(strict_types=1);

namespace App\Workshop\Application;

use App\Shared\Domain\Enum\SupportedLocale;
use App\Workshop\Application\Data\WorkshopData;
use App\Workshop\Domain\Entity\Workshop;
use App\Workshop\Domain\Repository\WorkshopRepositoryInterface;

final readonly class UpdateWorkshopHandler
{
    public function __construct(private WorkshopRepositoryInterface $workshops)
    {
    }

    public function __invoke(Workshop $workshop, WorkshopData $data): void
    {
        $workshop->updateTranslation(SupportedLocale::French, $data->titleFr, $data->descriptionFr);
        $workshop->updateTranslation(SupportedLocale::English, $data->titleEn, $data->descriptionEn);
        $workshop->updateClassification($data->category, $data->level);
        $this->workshops->save($workshop);
    }
}
