<?php

declare(strict_types=1);

namespace App\Workshop\Domain\Enum;

enum WorkshopCategory: string
{
    case Development = 'development';
    case Design = 'design';
    case Data = 'data';
    case Product = 'product';
    case Career = 'career';

    public function labelKey(): string
    {
        return 'enum.workshop_category.'.$this->value;
    }
}
