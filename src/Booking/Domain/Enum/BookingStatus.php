<?php

declare(strict_types=1);

namespace App\Booking\Domain\Enum;

enum BookingStatus: string
{
    case Confirmed = 'confirmed';
    case Waitlisted = 'waitlisted';
    case Cancelled = 'cancelled';

    public function labelKey(): string
    {
        return 'enum.booking_status.'.$this->value;
    }
}
