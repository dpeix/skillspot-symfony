<?php

declare(strict_types=1);

namespace App\Tests\Unit\Workshop;

use App\Tests\Support\DomainFactory;
use PHPUnit\Framework\TestCase;

final class WorkshopTest extends TestCase
{
    use DomainFactory;

    public function testPublicationRequiresAFutureBookableSession(): void
    {
        $workshop = $this->workshop();
        self::assertFalse($workshop->hasFutureSession(new \DateTimeImmutable()));

        $this->session($workshop);
        self::assertTrue($workshop->hasFutureSession(new \DateTimeImmutable()));
    }
}
