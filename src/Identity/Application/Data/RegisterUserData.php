<?php

declare(strict_types=1);

namespace App\Identity\Application\Data;

use App\Shared\Domain\Enum\SupportedLocale;
use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserData
{
    public SupportedLocale $preferredLocale = SupportedLocale::French;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $firstName = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $lastName = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 4096)]
    public string $plainPassword = '';

    #[Assert\IsTrue(message: 'identity.validation.accept_terms')]
    public bool $agreeTerms = false;
}
