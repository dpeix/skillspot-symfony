<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entity;

use App\Identity\Domain\Enum\OrganizerApplicationStatus;
use App\Identity\Domain\Enum\Role;
use App\Identity\Infrastructure\Doctrine\OrganizerApplicationRepository;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrganizerApplicationRepository::class)]
#[ORM\Table(name: 'organizer_application')]
class OrganizerApplication
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $applicant;

    #[ORM\Column(type: Types::TEXT)]
    private string $motivation;

    #[ORM\Column(length: 20)]
    private string $status = OrganizerApplicationStatus::Pending->value;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $reviewedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $decisionNote = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    public function __construct(User $applicant, string $motivation, ?\DateTimeImmutable $createdAt = null)
    {
        if (mb_strlen(trim($motivation)) < 40) {
            throw new BusinessRuleViolation('Votre motivation doit contenir au moins 40 caractères.');
        }

        $this->applicant = $applicant;
        $this->motivation = trim($motivation);
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplicant(): User
    {
        return $this->applicant;
    }

    public function getMotivation(): string
    {
        return $this->motivation;
    }

    public function getStatus(): OrganizerApplicationStatus
    {
        return OrganizerApplicationStatus::from($this->status);
    }

    public function getWorkflowState(): string
    {
        return $this->status;
    }

    public function setWorkflowState(string $status): void
    {
        $this->status = OrganizerApplicationStatus::from($status)->value;
    }

    public function recordDecision(User $reviewer, ?string $note, \DateTimeImmutable $now): void
    {
        if (OrganizerApplicationStatus::Pending !== $this->getStatus()) {
            throw new BusinessRuleViolation('Cette demande a déjà été traitée.');
        }

        $this->reviewedBy = $reviewer;
        $this->decisionNote = $note ? trim($note) : null;
        $this->reviewedAt = $now;
    }

    public function finalizeApproval(): void
    {
        $this->applicant->grantRole(Role::Organizer);
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getReviewedAt(): ?\DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function getDecisionNote(): ?string
    {
        return $this->decisionNote;
    }
}
