<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RequestResponseLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for paired HTTP request/response audit records.
 *
 * @extends ServiceEntityRepository<RequestResponseLog>
 */
final class RequestResponseLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RequestResponseLog::class);
    }

    public function append(RequestResponseLog $record): void
    {
        $manager = $this->getEntityManager();
        $manager->persist($record);
        $manager->flush();
    }

    /**
     * Number of records matching the criteria stored on the record itself.
     *
     * Payload contents are deliberately out of reach here: counting must stay
     * cheap on a large log table.
     */
    public function countForLog(?string $method = null, ?string $route = null, ?int $status = null): int
    {
        return (int) $this->logCriteria($method, $route, $status)
            ->select('COUNT(record.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * One window of records, newest responses first.
     *
     * @return RequestResponseLog[]
     */
    public function findForLogPage(
        ?string $method,
        ?string $route,
        ?int $status,
        int $offset,
        int $limit,
    ): array {
        return $this->logCriteria($method, $route, $status)
            ->orderBy('record.respondedAt', 'DESC')
            ->addOrderBy('record.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Releases the records loaded by a long scan.
     *
     * The identity map otherwise keeps every hydrated record (payloads
     * included) alive until the end of the request.
     */
    public function clear(): void
    {
        $this->getEntityManager()->clear();
    }

    private function logCriteria(?string $method, ?string $route, ?int $status): QueryBuilder
    {
        $builder = $this->createQueryBuilder('record');

        if ($method !== null) {
            $builder->andWhere('UPPER(record.method) = :method')
                ->setParameter('method', strtoupper($method));
        }

        if ($route !== null) {
            $builder->andWhere('LOWER(record.route) LIKE :route')
                ->setParameter('route', '%'.mb_strtolower($route).'%');
        }

        if ($status !== null) {
            $builder->andWhere('record.status = :status')
                ->setParameter('status', $status);
        }

        return $builder;
    }
}
