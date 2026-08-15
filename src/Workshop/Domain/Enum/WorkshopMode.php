<?php

declare(strict_types=1);

namespace App\Workshop\Domain\Enum;

enum WorkshopMode: string
{
    case Onsite = 'onsite';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::Onsite => 'Sur place',
            self::Online => 'En visioconférence',
        };
    }
}
