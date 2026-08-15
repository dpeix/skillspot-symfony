<?php

declare(strict_types=1);

namespace App\Workshop\Domain\Enum;

enum WorkshopStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
