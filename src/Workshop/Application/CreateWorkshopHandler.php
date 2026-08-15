<?php

declare(strict_types=1);

namespace App\Workshop\Application;

use App\Identity\Domain\Entity\User;
use App\Workshop\Application\Data\WorkshopData;
use App\Workshop\Domain\Entity\Workshop;
use App\Workshop\Domain\Repository\WorkshopRepositoryInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

final readonly class CreateWorkshopHandler
{
    public function __construct(
        private WorkshopRepositoryInterface $workshops,
        private SluggerInterface $slugger,
    ) {
    }

    public function __invoke(User $owner, WorkshopData $data): Workshop
    {
        $baseSlug = $this->slugger->slug($data->title)->lower()->toString();
        $workshop = new Workshop(
            $owner,
            $data->title,
            $this->workshops->nextAvailableSlug($baseSlug),
            $data->description,
            $data->category,
            $data->level,
        );
        $this->workshops->save($workshop);

        return $workshop;
    }
}
