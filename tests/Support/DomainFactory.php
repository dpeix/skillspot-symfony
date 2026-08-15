<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Identity\Domain\Entity\User;
use App\Shared\Domain\Enum\SupportedLocale;
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
        $workshop = new Workshop(
            $owner ?? $this->user(),
            WorkshopCategory::Development,
            WorkshopLevel::Intermediate,
        );
        $workshop->addTranslation(
            SupportedLocale::French,
            'Un atelier utile',
            'un-atelier-utile',
            'Une description suffisamment détaillée qui présente clairement les objectifs pédagogiques et les exercices pratiques proposés.',
        );
        $workshop->addTranslation(
            SupportedLocale::English,
            'A useful workshop',
            'a-useful-workshop',
            'A sufficiently detailed workshop description that clearly explains the learning outcomes and practical exercises.',
        );

        return $workshop;
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
