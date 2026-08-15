<?php

declare(strict_types=1);

namespace App\Workshop\Application\Data;

use App\Workshop\Domain\Enum\WorkshopMode;
use Symfony\Component\Validator\Constraints as Assert;

final class SessionData
{
    #[Assert\NotNull]
    #[Assert\GreaterThan('now')]
    public ?\DateTimeImmutable $startsAt = null;

    #[Assert\NotNull]
    #[Assert\GreaterThan(propertyPath: 'startsAt')]
    public ?\DateTimeImmutable $endsAt = null;

    #[Assert\Range(min: 1, max: 200)]
    public int $capacity = 12;

    public WorkshopMode $mode = WorkshopMode::Onsite;

    #[Assert\Length(max: 255)]
    public ?string $location = null;

    #[Assert\Url]
    #[Assert\Length(max: 500)]
    public ?string $meetingUrl = null;
}
