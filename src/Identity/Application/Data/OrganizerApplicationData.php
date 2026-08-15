<?php

declare(strict_types=1);

namespace App\Identity\Application\Data;

use Symfony\Component\Validator\Constraints as Assert;

final class OrganizerApplicationData
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 40, max: 2000)]
    public string $motivation = '';
}
