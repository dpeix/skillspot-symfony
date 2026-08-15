<?php

declare(strict_types=1);

namespace App\Workshop\Infrastructure\Doctrine;

use App\Identity\Domain\Entity\User;
use App\Shared\Domain\Enum\SupportedLocale;
use App\Workshop\Domain\Entity\Workshop;
use App\Workshop\Domain\Entity\WorkshopTranslation;
use App\Workshop\Domain\Enum\WorkshopStatus;
use App\Workshop\Domain\Repository\WorkshopRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
        $now = new \DateTimeImmutable();
        $identifierQuery = $this->createQueryBuilder('w')
            ->select('w.id')
            ->addSelect('MIN(s.startsAt) AS HIDDEN firstSession')
            ->join('w.sessions', 's')
            ->andWhere('w.status = :status')
            ->andWhere('s.startsAt > :now')
            ->andWhere('s.status = :sessionStatus')
            ->setParameter('status', WorkshopStatus::Published->value)
            ->setParameter('sessionStatus', 'scheduled')
            ->setParameter('now', $now)
            ->groupBy('w.id')
            ->orderBy('firstSession', 'ASC')
            ->setMaxResults($limit);
        $this->applyCatalogFilters($identifierQuery, $filters);

        /** @var list<array{id: int|string}> $identifierRows */
        $identifierRows = $identifierQuery->getQuery()->getArrayResult();
        $identifiers = array_map(static fn (array $row): int => (int) $row['id'], $identifierRows);
        if ([] === $identifiers) {
            return [];
        }

        $query = $this->createQueryBuilder('w')
            ->addSelect('t', 's', 'b')
            ->leftJoin('w.translations', 't')
            ->join('w.sessions', 's')
            ->leftJoin('s.bookings', 'b')
            ->andWhere('w.id IN (:identifiers)')
            ->andWhere('s.startsAt > :now')
            ->andWhere('s.status = :sessionStatus')
            ->setParameter('identifiers', $identifiers)
            ->setParameter('sessionStatus', 'scheduled')
            ->setParameter('now', $now)
            ->orderBy('s.startsAt', 'ASC');
        $this->applyCatalogFilters($query, $filters);

        /** @var list<Workshop> $result */
        $result = $query->getQuery()->getResult();

        return $result;
    }

    /** @param array<string, string> $filters */
    private function applyCatalogFilters(QueryBuilder $query, array $filters): void
    {
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
    }

    public function ownedBy(User $owner): array
    {
        /** @var list<Workshop> $result */
        $result = $this->createQueryBuilder('w')
            ->addSelect('t', 's', 'b')
            ->leftJoin('w.translations', 't')
            ->leftJoin('w.sessions', 's')
            ->leftJoin('s.bookings', 'b')
            ->andWhere('w.owner = :owner')
            ->setParameter('owner', $owner)
            ->orderBy('w.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findPublishedBySlug(SupportedLocale $locale, string $slug): ?Workshop
    {
        $result = $this->createQueryBuilder('w')
            ->addSelect('t', 's', 'b')
            ->join('w.translations', 'matchedTranslation')
            ->leftJoin('w.translations', 't')
            ->leftJoin('w.sessions', 's')
            ->leftJoin('s.bookings', 'b')
            ->andWhere('matchedTranslation.locale = :locale')
            ->andWhere('matchedTranslation.slug = :slug')
            ->andWhere('w.status = :status')
            ->setParameter('locale', $locale)
            ->setParameter('slug', $slug)
            ->setParameter('status', WorkshopStatus::Published->value)
            ->getQuery()
            ->getOneOrNullResult();

        if (null !== $result && !$result instanceof Workshop) {
            throw new \UnexpectedValueException('Doctrine returned an invalid workshop.');
        }

        return $result;
    }

    public function findPublishedByAnySlug(string $slug): ?Workshop
    {
        /** @var list<Workshop> $results */
        $results = $this->createQueryBuilder('w')
            ->addSelect('t', 's', 'b')
            ->join('w.translations', 'matchedTranslation')
            ->leftJoin('w.translations', 't')
            ->leftJoin('w.sessions', 's')
            ->leftJoin('s.bookings', 'b')
            ->andWhere('matchedTranslation.slug = :slug')
            ->andWhere('w.status = :status')
            ->setParameter('slug', $slug)
            ->setParameter('status', WorkshopStatus::Published->value)
            ->orderBy('matchedTranslation.locale', 'DESC')
            ->getQuery()
            ->getResult();

        return $results[0] ?? null;
    }

    public function save(Workshop $workshop): void
    {
        $this->getEntityManager()->persist($workshop);
        $this->getEntityManager()->flush();
    }

    public function nextAvailableSlug(SupportedLocale $locale, string $baseSlug): string
    {
        $slug = $baseSlug;
        $suffix = 2;
        $translations = $this->getEntityManager()->getRepository(WorkshopTranslation::class);
        while (null !== $translations->findOneBy(['locale' => $locale, 'slug' => $slug])) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
