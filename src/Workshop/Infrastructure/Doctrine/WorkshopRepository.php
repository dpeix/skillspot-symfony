<?php

declare(strict_types=1);

namespace App\Workshop\Infrastructure\Doctrine;

use App\Identity\Domain\Entity\User;
use App\Workshop\Domain\Entity\Workshop;
use App\Workshop\Domain\Enum\WorkshopStatus;
use App\Workshop\Domain\Repository\WorkshopRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Workshop> */
final class WorkshopRepository extends ServiceEntityRepository implements WorkshopRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workshop::class);
    }

    public function get(int $id): ?Workshop
    {
        return $this->find($id);
    }

    /**
     * @param array<string, string> $filters
     *
     * @return list<Workshop>
     */
    public function searchPublished(array $filters = [], int $limit = 24): array
    {
        $query = $this->createQueryBuilder('w')
            ->addSelect('s', 'b')
            ->leftJoin('w.sessions', 's')
            ->leftJoin('s.bookings', 'b')
            ->andWhere('w.status = :status')
            ->andWhere('s.startsAt > :now')
            ->andWhere('s.status = :sessionStatus')
            ->setParameter('status', WorkshopStatus::Published->value)
            ->setParameter('sessionStatus', 'scheduled')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('s.startsAt', 'ASC')
            ->setMaxResults($limit);

        foreach (['category', 'level'] as $field) {
            if (!empty($filters[$field])) {
                $query->andWhere(\sprintf('w.%s = :%s', $field, $field))->setParameter($field, $filters[$field]);
            }
        }

        if (!empty($filters['mode'])) {
            $query->andWhere('s.mode = :mode')->setParameter('mode', $filters['mode']);
        }

        if (!empty($filters['date'])) {
            $timezone = new \DateTimeZone('Europe/Paris');
            $start = \DateTimeImmutable::createFromFormat('!Y-m-d', $filters['date'], $timezone);
            if ($start instanceof \DateTimeImmutable) {
                $end = $start->modify('+1 day');
                $query->andWhere('s.startsAt >= :dateStart AND s.startsAt < :dateEnd')
                    ->setParameter('dateStart', $start->setTimezone(new \DateTimeZone('UTC')))
                    ->setParameter('dateEnd', $end->setTimezone(new \DateTimeZone('UTC')));
            } else {
                $query->andWhere('1 = 0');
            }
        }

        /** @var list<Workshop> $result */
        $result = $query->getQuery()->getResult();

        return $result;
    }

    public function ownedBy(User $owner): array
    {
        return $this->findBy(['owner' => $owner], ['createdAt' => 'DESC']);
    }

    public function findPublishedBySlug(string $slug): ?Workshop
    {
        $result = $this->createQueryBuilder('w')
            ->addSelect('s', 'b')
            ->leftJoin('w.sessions', 's')
            ->leftJoin('s.bookings', 'b')
            ->andWhere('w.slug = :slug')
            ->andWhere('w.status = :status')
            ->setParameter('slug', $slug)
            ->setParameter('status', WorkshopStatus::Published->value)
            ->getQuery()
            ->getOneOrNullResult();

        if (null !== $result && !$result instanceof Workshop) {
            throw new \UnexpectedValueException('Doctrine returned an invalid workshop.');
        }

        return $result;
    }

    public function save(Workshop $workshop): void
    {
        $this->getEntityManager()->persist($workshop);
        $this->getEntityManager()->flush();
    }

    public function nextAvailableSlug(string $baseSlug): string
    {
        $slug = $baseSlug;
        $suffix = 2;
        while (null !== $this->findOneBy(['slug' => $slug])) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
