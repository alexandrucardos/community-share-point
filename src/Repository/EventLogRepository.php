<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EventLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository for the append-only {@see EventLog} table.
 *
 * @extends ServiceEntityRepository<EventLog>
 */
final class EventLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventLog::class);
    }

    /**
     * Append a single entry. Entries are never updated or removed.
     */
    public function append(EventLog $record): void
    {
        $manager = $this->getEntityManager();
        $manager->persist($record);
        $manager->flush();
    }
}
