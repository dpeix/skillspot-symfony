<?php

declare(strict_types=1);

namespace App\Workshop\Domain\Entity;

use App\Shared\Domain\Enum\SupportedLocale;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'workshop_translation')]
#[ORM\UniqueConstraint(name: 'uniq_workshop_translation_locale', columns: ['workshop_id', 'locale'])]
#[ORM\UniqueConstraint(name: 'uniq_workshop_translation_slug', columns: ['locale', 'slug'])]
class WorkshopTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workshop $workshop;

    #[ORM\Column(length: 2, enumType: SupportedLocale::class)]
    private SupportedLocale $locale;

    #[ORM\Column(length: 160)]
    private string $title;

    #[ORM\Column(length: 190)]
    private string $slug;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    public function __construct(
        Workshop $workshop,
        SupportedLocale $locale,
        string $title,
        string $slug,
        string $description,
    ) {
        $this->assertContent($title, $slug, $description);
        $this->workshop = $workshop;
        $this->locale = $locale;
        $this->title = trim($title);
        $this->slug = trim($slug);
        $this->description = trim($description);
    }

    public function getLocale(): SupportedLocale
    {
        return $this->locale;
    }

    public function getWorkshop(): Workshop
    {
        return $this->workshop;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function update(string $title, string $description): void
    {
        $this->assertContent($title, $this->slug, $description);
        $this->title = trim($title);
        $this->description = trim($description);
    }

    private function assertContent(string $title, string $slug, string $description): void
    {
        if (mb_strlen(trim($title)) < 5 || mb_strlen(trim($description)) < 80 || '' === trim($slug)) {
            throw new BusinessRuleViolation('workshop.error.content_too_short');
        }
    }
}
