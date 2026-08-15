<?php

declare(strict_types=1);

namespace App\Workshop\Domain\Enum;

enum WorkshopLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    public function labelKey(): string
    {
        return 'enum.workshop_level.'.$this->value;
    }
}
