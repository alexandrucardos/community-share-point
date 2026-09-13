<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RequestResponseLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function update(RequestResponseLog $record): void
    {
        $this->getEntityManager()->flush();
    }
}
