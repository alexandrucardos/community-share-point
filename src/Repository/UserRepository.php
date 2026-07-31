<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository coupled to the {@see User} persistence model.
 *
 * Speaks only in terms of records; the domain-facing translation lives in
 * {@see \App\Repository\Adaptor\UserRepositoryAdaptor}.
 *
 * @extends ServiceEntityRepository<User>
 */
final class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Insert or update the record, keyed by its (application-assigned) id.
     */
    public function save(User $record): void
    {
        $manager = $this->getEntityManager();
        $existing = $this->find($record->id);

        if ($existing === null) {
            $manager->persist($record);
        } else {
            $existing->email = $record->email;
            $existing->password = $record->password;
            $existing->contactInfo = $record->contactInfo;
            $existing->groupId = $record->groupId;
        }

        $manager->flush();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByEmailAndGroupId(string $email, string $groupId): ?User
    {
        return $this->findOneBy(['email' => $email, 'groupId' => $groupId]);
    }

    /**
     * @return User[]
     */
    public function findByGroupId(string $groupId): array
    {
        return $this->findBy(['groupId' => $groupId]);
    }
}
