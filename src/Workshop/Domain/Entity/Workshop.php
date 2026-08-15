<?php

declare(strict_types=1);

namespace App\Workshop\Domain\Entity;

use App\Identity\Domain\Entity\User;
use App\Shared\Domain\Enum\SupportedLocale;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use App\Workshop\Domain\Enum\WorkshopCategory;
use App\Workshop\Domain\Enum\WorkshopLevel;
use App\Workshop\Domain\Enum\WorkshopStatus;
use App\Workshop\Infrastructure\Doctrine\WorkshopRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkshopRepository::class)]
#[ORM\Table(name: 'workshop')]
class Workshop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\Column(length: 30, enumType: WorkshopCategory::class)]
    private WorkshopCategory $category;

    #[ORM\Column(length: 30, enumType: WorkshopLevel::class)]
    private WorkshopLevel $level;

    #[ORM\Column(length: 20)]
    private string $status = WorkshopStatus::Draft->value;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<string, WorkshopTranslation> */
    #[ORM\OneToMany(mappedBy: 'workshop', targetEntity: WorkshopTranslation::class, cascade: ['persist'], orphanRemoval: true, indexBy: 'locale')]
    private Collection $translations;

    /** @var Collection<int, WorkshopSession> */
    #[ORM\OneToMany(mappedBy: 'workshop', targetEntity: WorkshopSession::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['startsAt' => 'ASC'])]
    private Collection $sessions;

    public function __construct(
        User $owner,
        WorkshopCategory $category,
        WorkshopLevel $level,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        $this->owner = $owner;
        $this->category = $category;
        $this->level = $level;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
        $this->translations = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function getTitle(): string
    {
        return $this->translation(SupportedLocale::French)->getTitle();
    }

    public function getSlug(): string
    {
        return $this->translation(SupportedLocale::French)->getSlug();
    }

    public function getDescription(): string
    {
        return $this->translation(SupportedLocale::French)->getDescription();
    }

    public function getCategory(): WorkshopCategory
    {
        return $this->category;
    }

    public function getLevel(): WorkshopLevel
    {
        return $this->level;
    }

    public function getStatus(): WorkshopStatus
    {
        return WorkshopStatus::from($this->status);
    }

    public function getWorkflowState(): string
    {
        return $this->status;
    }

    public function setWorkflowState(string $status): void
    {
        $this->status = WorkshopStatus::from($status)->value;
    }

    /** @return Collection<string, WorkshopTranslation> */
    public function getTranslations(): Collection
    {
        return $this->translations;
    }

    public function addTranslation(
        SupportedLocale $locale,
        string $title,
        string $slug,
        string $description,
    ): void {
        if ($this->hasTranslation($locale)) {
            throw new BusinessRuleViolation('workshop.error.duplicate_translation');
        }

        $this->translations->set($locale->value, new WorkshopTranslation($this, $locale, $title, $slug, $description));
    }

    public function updateTranslation(SupportedLocale $locale, string $title, string $description): void
    {
        if (WorkshopStatus::Archived === $this->getStatus()) {
            throw new BusinessRuleViolation('workshop.error.archived');
        }

        $this->translation($locale)->update($title, $description);
    }

    public function translation(SupportedLocale|string $locale): WorkshopTranslation
    {
        $locale = $locale instanceof SupportedLocale ? $locale : SupportedLocale::fromString($locale);
        $translation = $this->translations->get($locale->value);
        if (!$translation instanceof WorkshopTranslation) {
            throw new BusinessRuleViolation('workshop.error.missing_translation', ['%locale%' => $locale->value]);
        }

        return $translation;
    }

    public function hasTranslation(SupportedLocale $locale): bool
    {
        return $this->translations->get($locale->value) instanceof WorkshopTranslation;
    }

    public function hasAllTranslations(): bool
    {
        foreach (SupportedLocale::cases() as $locale) {
            if (!$this->hasTranslation($locale)) {
                return false;
            }
        }

        return true;
    }

    /** @return Collection<int, WorkshopSession> */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(WorkshopSession $session): void
    {
        if ($session->getWorkshop() !== $this) {
            throw new BusinessRuleViolation('workshop.error.invalid_session_owner');
        }

        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
        }
    }

    public function hasFutureSession(\DateTimeImmutable $now): bool
    {
        foreach ($this->sessions as $session) {
            if ($session->isBookableAt($now)) {
                return true;
            }
        }

        return false;
    }

    public function updateClassification(WorkshopCategory $category, WorkshopLevel $level): void
    {
        if (WorkshopStatus::Archived === $this->getStatus()) {
            throw new BusinessRuleViolation('workshop.error.archived');
        }

        $this->category = $category;
        $this->level = $level;
    }
}
