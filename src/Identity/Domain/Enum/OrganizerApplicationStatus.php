<?php

declare(strict_types=1);

namespace App\Identity\Domain\Enum;

enum OrganizerApplicationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
