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

    public function label(): string
    {
        return match ($this) {
            self::Development => 'Développement',
            self::Design => 'Design',
            self::Data => 'Data',
            self::Product => 'Produit',
            self::Career => 'Carrière',
        };
    }
}
