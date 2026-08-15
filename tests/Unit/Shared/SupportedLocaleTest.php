<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared;

use App\Shared\Domain\Enum\SupportedLocale;
use PHPUnit\Framework\TestCase;

final class SupportedLocaleTest extends TestCase
{
    public function testOnlyFrenchAndEnglishAreSupported(): void
    {
        self::assertSame(['fr', 'en'], array_column(SupportedLocale::cases(), 'value'));
        self::assertSame(SupportedLocale::French, SupportedLocale::default());
        self::assertSame(SupportedLocale::English, SupportedLocale::fromString('en-GB'));
        self::assertSame(SupportedLocale::French, SupportedLocale::fromString('de'));
        self::assertSame(SupportedLocale::English, SupportedLocale::French->other());
    }
}
