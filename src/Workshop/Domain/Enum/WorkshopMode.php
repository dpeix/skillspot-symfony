<?php

declare(strict_types=1);

namespace App\Workshop\Domain\Enum;

enum WorkshopMode: string
{
    case Onsite = 'onsite';
    case Online = 'online';

    public function labelKey(): string
    {
        return 'enum.workshop_mode.'.$this->value;
    }
}
