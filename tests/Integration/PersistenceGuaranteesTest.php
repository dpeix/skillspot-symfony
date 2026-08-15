<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Booking\Domain\Entity\Booking;
use App\Booking\Domain\Enum\BookingStatus;
use App\Booking\Infrastructure\Doctrine\BookingRepository;
use App\Identity\Domain\Entity\User;
use App\Identity\Infrastructure\Doctrine\UserRepository;
use App\Workshop\Domain\Entity\WorkshopSession;
use App\Workshop\Infrastructure\Doctrine\WorkshopSessionRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PersistenceGuaranteesTest extends KernelTestCase
{
    public function testDatabaseRejectsDuplicateBookings(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->getConnection()->beginTransaction();

        try {
            $user = self::getContainer()->get(UserRepository::class)->findByEmail('participant@skillspot.local');
            self::assertInstanceOf(User::class, $user);
            $existing = self::getContainer()->get(BookingRepository::class)->forUser($user)[0] ?? null;
            self::assertInstanceOf(Booking::class, $existing);

            $entityManager->persist(new Booking($user, $existing->getSession(), BookingStatus::Waitlisted));

            try {
                $entityManager->flush();
                self::fail('PostgreSQL accepted a duplicate attendee/session pair.');
            } catch (UniqueConstraintViolationException) {
                self::addToAssertionCount(1);
            }
        } finally {
            if ($entityManager->getConnection()->isTransactionActive()) {
                $entityManager->getConnection()->rollBack();
            }
        }
    }

    public function testSessionRowsSerializeConcurrentWriters(): void
    {
        self::bootKernel();
        $parameters = self::getContainer()->get(EntityManagerInterface::class)->getConnection()->getParams();
        $first = DriverManager::getConnection($parameters);
        $second = DriverManager::getConnection($parameters);

        try {
            $sessionId = (int) $first->fetchOne('SELECT id FROM workshop_session ORDER BY id LIMIT 1');
            self::assertGreaterThan(0, $sessionId);
            $first->beginTransaction();
            $second->beginTransaction();
            $first->executeQuery('SELECT id FROM workshop_session WHERE id = ? FOR UPDATE', [$sessionId]);
            $second->executeStatement("SET LOCAL lock_timeout = '100ms'");

            try {
                $second->executeQuery('SELECT id FROM workshop_session WHERE id = ? FOR UPDATE', [$sessionId]);
                self::fail('A concurrent writer acquired an already locked session row.');
            } catch (DriverException $exception) {
                self::assertStringContainsString('lock timeout', mb_strtolower($exception->getMessage()));
            }
        } finally {
            if ($second->isTransactionActive()) {
                $second->rollBack();
            }
            if ($first->isTransactionActive()) {
                $first->rollBack();
            }
            $second->close();
            $first->close();
        }
    }

    public function testDatabaseRejectsAConfirmedBookingAboveCapacity(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $candidate = self::getContainer()->get(UserRepository::class)->findByEmail('candidate@skillspot.local');
            $session = self::getContainer()->get(WorkshopSessionRepository::class)->findOneBy(['capacity' => 2]);
            self::assertInstanceOf(User::class, $candidate);
            self::assertInstanceOf(WorkshopSession::class, $session);

            try {
                $connection->executeStatement(
                    "INSERT INTO booking (attendee_id, session_id, status, attendance, queued_at, updated_at) VALUES (?, ?, 'confirmed', 'pending', NOW(), NOW())",
                    [$candidate->getId(), $session->getId()],
                );
                self::fail('PostgreSQL accepted a confirmed booking above session capacity.');
            } catch (DriverException $exception) {
                self::assertStringContainsString('Session capacity exceeded', $exception->getMessage());
            }
        } finally {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
        }
    }
}
