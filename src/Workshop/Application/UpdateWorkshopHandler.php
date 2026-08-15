<?php

declare(strict_types=1);

namespace App\Workshop\Application;

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
        $workshop->updateDetails($data->title, $data->description, $data->category, $data->level);
        $this->workshops->save($workshop);
    }
}
