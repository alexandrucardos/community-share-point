<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository coupled to the {@see UserGroup} persistence model.
 *
 * @extends ServiceEntityRepository<UserGroup>
 */
final class UserGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserGroup::class);
    }

    /**
     * Insert or update the record, keyed by its (application-assigned) id.
     */
    public function save(UserGroup $record): void
    {
        $manager = $this->getEntityManager();
        $existing = $this->find($record->id);

        if ($existing === null) {
            $manager->persist($record);
        } else {
            $existing->name = $record->name;
        }

        $manager->flush();
    }
}
