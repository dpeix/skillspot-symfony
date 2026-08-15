<?php

declare(strict_types=1);

namespace App\Workshop\Domain\Entity;

use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Enum\BookingStatus;
use App\Shared\Domain\Exception\BusinessRuleViolation;
use App\Workshop\Domain\Enum\SessionStatus;
use App\Workshop\Domain\Enum\WorkshopMode;
use App\Workshop\Infrastructure\Doctrine\WorkshopSessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkshopSessionRepository::class)]
#[ORM\Table(name: 'workshop_session')]
class WorkshopSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Workshop $workshop;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $endsAt;

    #[ORM\Column]
    private int $capacity;

    #[ORM\Column(length: 20, enumType: WorkshopMode::class)]
    private WorkshopMode $mode;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $meetingUrl;

    #[ORM\Column(length: 20, enumType: SessionStatus::class)]
    private SessionStatus $status = SessionStatus::Scheduled;

    /** @var Collection<int, Booking> */
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: Booking::class)]
    private Collection $bookings;

    public function __construct(
        Workshop $workshop,
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $endsAt,
        int $capacity,
        WorkshopMode $mode,
        ?string $location = null,
        ?string $meetingUrl = null,
    ) {
        $startsAt = $startsAt->setTimezone(new \DateTimeZone('UTC'));
        $endsAt = $endsAt->setTimezone(new \DateTimeZone('UTC'));
        $this->assertSchedule($startsAt, $endsAt, $capacity, $mode, $location, $meetingUrl);
        $this->workshop = $workshop;
        $this->startsAt = $startsAt;
        $this->endsAt = $endsAt;
        $this->capacity = $capacity;
        $this->mode = $mode;
        $this->location = WorkshopMode::Onsite === $mode && $location ? trim($location) : null;
        $this->meetingUrl = WorkshopMode::Online === $mode && $meetingUrl ? trim($meetingUrl) : null;
        $this->bookings = new ArrayCollection();
        $workshop->addSession($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorkshop(): Workshop
    {
        return $this->workshop;
    }

    public function getStartsAt(): \DateTimeImmutable
    {
        return $this->startsAt;
    }

    public function getEndsAt(): \DateTimeImmutable
    {
        return $this->endsAt;
    }

    public function getCapacity(): int
    {
        return $this->capacity;
    }

    public function getMode(): WorkshopMode
    {
        return $this->mode;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getMeetingUrl(): ?string
    {
        return $this->meetingUrl;
    }

    public function getStatus(): SessionStatus
    {
        return $this->status;
    }

    /** @return Collection<int, Booking> */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): void
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
        }
    }

    public function confirmedCount(): int
    {
        return $this->bookings->filter(
            static fn (Booking $booking): bool => BookingStatus::Confirmed === $booking->getStatus(),
        )->count();
    }

    public function remainingSeats(): int
    {
        return max(0, $this->capacity - $this->confirmedCount());
    }

    public function isBookableAt(\DateTimeImmutable $now): bool
    {
        return SessionStatus::Scheduled === $this->status && $this->startsAt > $now;
    }

    public function overlaps(self $other): bool
    {
        return $this->startsAt < $other->endsAt && $this->endsAt > $other->startsAt;
    }

    public function canBeCancelledByAttendeeAt(\DateTimeImmutable $now): bool
    {
        return $now <= $this->startsAt->modify('-24 hours');
    }

    public function cancel(): void
    {
        if (SessionStatus::Scheduled !== $this->status) {
            throw new BusinessRuleViolation('session.error.only_scheduled_cancellation');
        }

        $this->status = SessionStatus::Cancelled;
    }

    public function complete(\DateTimeImmutable $now): void
    {
        if (SessionStatus::Scheduled !== $this->status || $now < $this->endsAt) {
            throw new BusinessRuleViolation('session.error.cannot_complete');
        }

        $this->status = SessionStatus::Completed;
    }

    private function assertSchedule(
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $endsAt,
        int $capacity,
        WorkshopMode $mode,
        ?string $location,
        ?string $meetingUrl,
    ): void {
        if ($endsAt <= $startsAt || $capacity < 1 || $capacity > 200) {
            throw new BusinessRuleViolation('session.error.invalid_schedule');
        }

        if (WorkshopMode::Onsite === $mode && !$location) {
            throw new BusinessRuleViolation('session.error.location_required');
        }

        if (WorkshopMode::Online === $mode && (!filter_var($meetingUrl, \FILTER_VALIDATE_URL))) {
            throw new BusinessRuleViolation('session.error.meeting_url_required');
        }
    }
}
