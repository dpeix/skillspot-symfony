<?php

declare(strict_types=1);

namespace App\Booking\Domain\Enum;

enum AttendanceStatus: string
{
    case Pending = 'pending';
    case Attended = 'attended';
    case NoShow = 'no_show';

    public function labelKey(): string
    {
        return 'enum.attendance_status.'.$this->value;
    }
}
