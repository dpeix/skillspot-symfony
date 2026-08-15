<?php

declare(strict_types=1);

namespace App\Booking\Domain\Entity;

use App\Booking\Domain\Enum\AttendanceStatus;
use App\Booking\Domain\Enum\BookingStatus;
use App\Booking\Infrastructure\Doctrine\BookingRepository;
use App\Identity\Domain\Entity\User;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use App\Workshop\Domain\Entity\WorkshopSession;
use App\Workshop\Domain\Enum\SessionStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'booking')]
#[ORM\UniqueConstraint(name: 'uniq_booking_attendee_session', columns: ['attendee_id', 'session_id'])]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $attendee;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private WorkshopSession $session;

    #[ORM\Column(length: 20, enumType: BookingStatus::class)]
    private BookingStatus $status;

    #[ORM\Column(length: 20, enumType: AttendanceStatus::class)]
    private AttendanceStatus $attendance = AttendanceStatus::Pending;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $queuedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $reminderSentAt = null;

    public function __construct(
        User $attendee,
        WorkshopSession $session,
        BookingStatus $status,
        ?\DateTimeImmutable $now = null,
    ) {
        $this->attendee = $attendee;
        $this->session = $session;
        $this->status = $status;
        $this->queuedAt = $now ?? new \DateTimeImmutable();
        $this->updatedAt = $this->queuedAt;
        $session->addBooking($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttendee(): User
    {
        return $this->attendee;
    }

    public function getSession(): WorkshopSession
    {
        return $this->session;
    }

    public function getStatus(): BookingStatus
    {
        return $this->status;
    }

    public function getAttendance(): AttendanceStatus
    {
        return $this->attendance;
    }

    public function getQueuedAt(): \DateTimeImmutable
    {
        return $this->queuedAt;
    }

    public function reactivate(BookingStatus $status, \DateTimeImmutable $now): void
    {
        if (BookingStatus::Cancelled !== $this->status) {
            throw new BusinessRuleViolation('booking.error.already_active');
        }

        $this->status = $status;
        $this->attendance = AttendanceStatus::Pending;
        $this->queuedAt = $now;
        $this->updatedAt = $now;
    }

    public function cancelByAttendee(\DateTimeImmutable $now): void
    {
        if (!$this->session->canBeCancelledByAttendeeAt($now)) {
            throw new BusinessRuleViolation('booking.error.cancellation_deadline');
        }

        $this->cancel($now);
    }

    public function cancel(\DateTimeImmutable $now): void
    {
        if (BookingStatus::Cancelled === $this->status) {
            throw new BusinessRuleViolation('booking.error.already_cancelled');
        }

        $this->status = BookingStatus::Cancelled;
        $this->attendance = AttendanceStatus::Pending;
        $this->updatedAt = $now;
    }

    public function promote(\DateTimeImmutable $now): void
    {
        if (BookingStatus::Waitlisted !== $this->status) {
            throw new BusinessRuleViolation('booking.error.only_waitlisted_promotion');
        }

        $this->status = BookingStatus::Confirmed;
        $this->updatedAt = $now;
    }

    public function markAttendance(AttendanceStatus $attendance, \DateTimeImmutable $now): void
    {
        if (BookingStatus::Confirmed !== $this->status || SessionStatus::Completed !== $this->session->getStatus()) {
            throw new BusinessRuleViolation('booking.error.attendance_after_completion');
        }

        if (AttendanceStatus::Pending === $attendance) {
            throw new BusinessRuleViolation('booking.error.invalid_attendance');
        }

        $this->attendance = $attendance;
        $this->updatedAt = $now;
    }

    public function markReminderSent(\DateTimeImmutable $now): void
    {
        $this->reminderSentAt = $now;
    }
}
