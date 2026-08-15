<?php

declare(strict_types=1);

namespace App\Workshop\Application\Data;

use App\Workshop\Domain\Enum\WorkshopCategory;
use App\Workshop\Domain\Enum\WorkshopLevel;
use Symfony\Component\Validator\Constraints as Assert;

final class WorkshopData
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 160)]
    public string $title = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 80, max: 5000)]
    public string $description = '';

    #[Assert\NotNull]
    public WorkshopCategory $category = WorkshopCategory::Development;

    #[Assert\NotNull]
    public WorkshopLevel $level = WorkshopLevel::Beginner;
}
