<?php

declare(strict_types=1);

namespace App\Shared\Domain\Enum;

enum SupportedLocale: string
{
    case French = 'fr';
    case English = 'en';

    public static function default(): self
    {
        return self::French;
    }

    public static function fromString(string $locale): self
    {
        return self::tryFrom(mb_strtolower(substr($locale, 0, 2))) ?? self::default();
    }

    public function other(): self
    {
        return self::French === $this ? self::English : self::French;
    }

    public function labelKey(): string
    {
        return 'locale.'.$this->value;
    }
}
