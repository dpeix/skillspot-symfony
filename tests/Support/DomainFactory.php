<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Identity\Domain\Entity\User;
use App\Workshop\Domain\Entity\Workshop;
use App\Workshop\Domain\Entity\WorkshopSession;
use App\Workshop\Domain\Enum\WorkshopCategory;
use App\Workshop\Domain\Enum\WorkshopLevel;
use App\Workshop\Domain\Enum\WorkshopMode;

trait DomainFactory
{
    private function user(string $email = 'jane@example.test'): User
    {
        $user = new User($email, 'Jane', 'Doe', 'hashed-password');
        $user->verify();

        return $user;
    }

    private function workshop(?User $owner = null): Workshop
    {
        return new Workshop(
            $owner ?? $this->user(),
            'A useful workshop',
            'a-useful-workshop',
            'A sufficiently detailed workshop description that clearly explains the learning outcomes and practical exercises.',
            WorkshopCategory::Development,
            WorkshopLevel::Intermediate,
        );
    }

    private function session(
        ?Workshop $workshop = null,
        string $startsAt = '+3 days',
        string $endsAt = '+3 days 2 hours',
        int $capacity = 2,
    ): WorkshopSession {
        return new WorkshopSession(
            $workshop ?? $this->workshop(),
            new \DateTimeImmutable($startsAt),
            new \DateTimeImmutable($endsAt),
            $capacity,
            WorkshopMode::Online,
            meetingUrl: 'https://meet.example.test/room',
        );
    }
}
