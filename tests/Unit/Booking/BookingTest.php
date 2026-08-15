<?php

declare(strict_types=1);

namespace App\Tests\Unit\Booking;

use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Enum\AttendanceStatus;
use App\Booking\Domain\Enum\BookingStatus;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use App\Tests\Support\DomainFactory;
use PHPUnit\Framework\TestCase;

final class BookingTest extends TestCase
{
    use DomainFactory;

    public function testOnlyWaitlistedBookingsCanBePromoted(): void
    {
        $booking = new Booking($this->user(), $this->session(), BookingStatus::Waitlisted);
        $booking->promote(new \DateTimeImmutable());

        self::assertSame(BookingStatus::Confirmed, $booking->getStatus());
    }

    public function testLateCancellationIsRejected(): void
    {
        $booking = new Booking(
            $this->user(),
            $this->session(startsAt: '2030-01-03 10:00', endsAt: '2030-01-03 12:00'),
            BookingStatus::Confirmed,
        );

        $this->expectException(BusinessRuleViolation::class);
        $booking->cancelByAttendee(new \DateTimeImmutable('2030-01-02 10:01'));
    }

    public function testAttendanceCanOnlyBeMarkedAfterCompletion(): void
    {
        $booking = new Booking($this->user(), $this->session(), BookingStatus::Confirmed);

        $this->expectException(BusinessRuleViolation::class);
        $booking->markAttendance(AttendanceStatus::Attended, new \DateTimeImmutable());
    }

    public function testAttendanceIsRecordedForACompletedSession(): void
    {
        $session = $this->session(startsAt: '-3 hours', endsAt: '-1 hour');
        $session->complete(new \DateTimeImmutable());
        $booking = new Booking($this->user(), $session, BookingStatus::Confirmed);
        $booking->markAttendance(AttendanceStatus::Attended, new \DateTimeImmutable());

        self::assertSame(AttendanceStatus::Attended, $booking->getAttendance());
    }
}
