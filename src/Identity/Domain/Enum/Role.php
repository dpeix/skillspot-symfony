<?php

declare(strict_types=1);

namespace App\Identity\Domain\Enum;

enum Role: string
{
    case User = 'ROLE_USER';
    case Organizer = 'ROLE_ORGANIZER';
    case Admin = 'ROLE_ADMIN';
}
