<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class BusinessRuleViolation extends \DomainException
{
    /** @param array<string, scalar> $parameters */
    public function __construct(string $translationKey, private readonly array $parameters = [])
    {
        parent::__construct($translationKey);
    }

    public function getTranslationKey(): string
    {
        return $this->getMessage();
    }

    /** @return array<string, scalar> */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
