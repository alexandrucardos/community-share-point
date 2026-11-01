<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ErrorLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for the append-only {@see ErrorLog} table.
 *
 * @extends ServiceEntityRepository<ErrorLog>
 */
final class ErrorLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ErrorLog::class);
    }

    /**
     * Append a single entry. Entries are never updated or removed.
     */
    public function append(ErrorLog $record): void
    {
        $manager = $this->getEntityManager();
        $manager->persist($record);
        $manager->flush();
    }

    /**
     * Newest entries first, bounded so the error-log page stays cheap on a
     * table that only ever grows.
     *
     * @return ErrorLog[]
     */
    public function findNewest(int $limit): array
    {
        return $this->findBy([], ['createdAt' => 'DESC'], $limit);
    }
}
