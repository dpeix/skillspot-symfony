<?php

declare(strict_types=1);

namespace App\Workshop\Application;

use App\Identity\Domain\Entity\User;
use App\Shared\Domain\Enum\SupportedLocale;
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
        $workshop = new Workshop(
            $owner,
            $data->category,
            $data->level,
        );
        $workshop->addTranslation(
            SupportedLocale::French,
            $data->titleFr,
            $this->slug(SupportedLocale::French, $data->titleFr),
            $data->descriptionFr,
        );
        $workshop->addTranslation(
            SupportedLocale::English,
            $data->titleEn,
            $this->slug(SupportedLocale::English, $data->titleEn),
            $data->descriptionEn,
        );
        $this->workshops->save($workshop);

        return $workshop;
    }

    private function slug(SupportedLocale $locale, string $title): string
    {
        $baseSlug = $this->slugger->slug($title)->lower()->toString();

        return $this->workshops->nextAvailableSlug($locale, $baseSlug);
    }
}
