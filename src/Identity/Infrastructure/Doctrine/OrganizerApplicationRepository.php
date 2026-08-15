<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Doctrine;

use App\Identity\Domain\Entity\OrganizerApplication;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\OrganizerApplicationStatus;
use App\Identity\Domain\Repository\OrganizerApplicationRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<OrganizerApplication> */
final class OrganizerApplicationRepository extends ServiceEntityRepository implements OrganizerApplicationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizerApplication::class);
    }

    public function hasPendingFor(User $user): bool
    {
        return null !== $this->findOneBy(['applicant' => $user, 'status' => OrganizerApplicationStatus::Pending->value]);
    }

    public function pending(): array
    {
        return $this->findBy(['status' => OrganizerApplicationStatus::Pending->value], ['createdAt' => 'ASC']);
    }

    public function save(OrganizerApplication $application): void
    {
        $this->getEntityManager()->persist($application);
        $this->getEntityManager()->flush();
    }
}
