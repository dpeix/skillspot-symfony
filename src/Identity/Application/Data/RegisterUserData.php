<?php

declare(strict_types=1);

namespace App\Identity\Application\Data;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserData
{
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

    #[Assert\IsTrue(message: 'Vous devez accepter les conditions d’utilisation.')]
    public bool $agreeTerms = false;
}
