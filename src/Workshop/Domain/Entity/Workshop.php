<?php

declare(strict_types=1);

namespace App\Workshop\Domain\Entity;

use App\Identity\Domain\Entity\User;
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
#[ORM\UniqueConstraint(name: 'uniq_workshop_slug', columns: ['slug'])]
class Workshop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\Column(length: 160)]
    private string $title;

    #[ORM\Column(length: 190)]
    private string $slug;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column(length: 30, enumType: WorkshopCategory::class)]
    private WorkshopCategory $category;

    #[ORM\Column(length: 30, enumType: WorkshopLevel::class)]
    private WorkshopLevel $level;

    #[ORM\Column(length: 20)]
    private string $status = WorkshopStatus::Draft->value;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, WorkshopSession> */
    #[ORM\OneToMany(mappedBy: 'workshop', targetEntity: WorkshopSession::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['startsAt' => 'ASC'])]
    private Collection $sessions;

    public function __construct(
        User $owner,
        string $title,
        string $slug,
        string $description,
        WorkshopCategory $category,
        WorkshopLevel $level,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        if (mb_strlen(trim($title)) < 5 || mb_strlen(trim($description)) < 80) {
            throw new BusinessRuleViolation('Le titre ou la description de l’atelier est trop court.');
        }

        $this->owner = $owner;
        $this->title = trim($title);
        $this->slug = $slug;
        $this->description = trim($description);
        $this->category = $category;
        $this->level = $level;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
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

    /** @return Collection<int, WorkshopSession> */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(WorkshopSession $session): void
    {
        if ($session->getWorkshop() !== $this) {
            throw new BusinessRuleViolation('La session doit appartenir à cet atelier.');
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

    public function updateDetails(
        string $title,
        string $description,
        WorkshopCategory $category,
        WorkshopLevel $level,
    ): void {
        if (WorkshopStatus::Archived === $this->getStatus()) {
            throw new BusinessRuleViolation('Un atelier archivé ne peut plus être modifié.');
        }

        if (mb_strlen(trim($title)) < 5 || mb_strlen(trim($description)) < 80) {
            throw new BusinessRuleViolation('Le titre ou la description de l’atelier est trop court.');
        }

        $this->title = trim($title);
        $this->description = trim($description);
        $this->category = $category;
        $this->level = $level;
    }
}
