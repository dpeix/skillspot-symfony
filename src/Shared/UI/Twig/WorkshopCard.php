<?php

declare(strict_types=1);

namespace App\Shared\UI\Twig;

use App\Workshop\Domain\Entity\Workshop;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('WorkshopCard')]
final class WorkshopCard
{
    public Workshop $workshop;
}
