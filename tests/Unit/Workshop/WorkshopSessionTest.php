<?php

declare(strict_types=1);

namespace App\Tests\Unit\Workshop;

use App\Shared\Domain\Exception\BusinessRuleViolation;
use App\Tests\Support\DomainFactory;
use App\Workshop\Domain\Entity\WorkshopSession;
use App\Workshop\Domain\Enum\WorkshopMode;
use PHPUnit\Framework\TestCase;

final class WorkshopSessionTest extends TestCase
{
    use DomainFactory;

    public function testItRequiresAValidSchedule(): void
    {
        $this->expectException(BusinessRuleViolation::class);
        new WorkshopSession(
            $this->workshop(),
            new \DateTimeImmutable('+2 days 12:00'),
            new \DateTimeImmutable('+2 days 10:00'),
            10,
            WorkshopMode::Online,
            meetingUrl: 'https://meet.example.test/room',
        );
    }

    public function testItRequiresTheLocationMatchingItsMode(): void
    {
        $this->expectException(BusinessRuleViolation::class);
        new WorkshopSession(
            $this->workshop(),
            new \DateTimeImmutable('+2 days'),
            new \DateTimeImmutable('+2 days 2 hours'),
            10,
            WorkshopMode::Onsite,
        );
    }

    public function testCancellationClosesTwentyFourHoursBeforeStart(): void
    {
        $session = $this->session(startsAt: '2030-01-03 10:00', endsAt: '2030-01-03 12:00');

        self::assertTrue($session->canBeCancelledByAttendeeAt(new \DateTimeImmutable('2030-01-02 10:00')));
        self::assertFalse($session->canBeCancelledByAttendeeAt(new \DateTimeImmutable('2030-01-02 10:01')));
    }

    public function testOverlapUsesHalfOpenIntervals(): void
    {
        $first = $this->session(startsAt: '2030-01-03 10:00', endsAt: '2030-01-03 12:00');
        $overlap = $this->session(startsAt: '2030-01-03 11:00', endsAt: '2030-01-03 13:00');
        $following = $this->session(startsAt: '2030-01-03 12:00', endsAt: '2030-01-03 14:00');

        self::assertTrue($first->overlaps($overlap));
        self::assertFalse($first->overlaps($following));
    }
}
