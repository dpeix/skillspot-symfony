<?php

declare(strict_types=1);

namespace App\Booking\Domain\Enum;

enum BookingStatus: string
{
    case Confirmed = 'confirmed';
    case Waitlisted = 'waitlisted';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmée',
            self::Waitlisted => 'Liste d’attente',
            self::Cancelled => 'Annulée',
        };
    }
}
