<?php

declare(strict_types=1);

namespace App\Workshop\Domain\Enum;

enum SessionStatus: string
{
    case Scheduled = 'scheduled';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function labelKey(): string
    {
        return 'enum.session_status.'.$this->value;
    }
}
