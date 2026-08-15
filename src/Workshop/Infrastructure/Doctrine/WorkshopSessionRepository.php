<?php

declare(strict_types=1);

namespace App\Workshop\Infrastructure\Doctrine;

use App\Shared\Domain\Exception\BusinessRuleViolation;
use App\Workshop\Domain\Entity\WorkshopSession;
use App\Workshop\Domain\Enum\SessionStatus;
use App\Workshop\Domain\Repository\WorkshopSessionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<WorkshopSession> */
final class WorkshopSessionRepository extends ServiceEntityRepository implements WorkshopSessionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkshopSession::class);
    }

    public function get(int $id): ?WorkshopSession
    {
        return $this->find($id);
    }

    public function lock(int $id): WorkshopSession
    {
        $session = $this->getEntityManager()->find(WorkshopSession::class, $id, LockMode::PESSIMISTIC_WRITE);
        if (!$session instanceof WorkshopSession) {
            throw new BusinessRuleViolation('session.error.not_found');
        }

        return $session;
    }

    public function endedBefore(\DateTimeImmutable $now): array
    {
        /** @var list<WorkshopSession> $result */
        $result = $this->createQueryBuilder('s')
            ->andWhere('s.status = :status')
            ->andWhere('s.endsAt <= :now')
            ->setParameter('status', SessionStatus::Scheduled)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function save(WorkshopSession $session): void
    {
        $this->getEntityManager()->persist($session);
        $this->getEntityManager()->flush();
    }
}
