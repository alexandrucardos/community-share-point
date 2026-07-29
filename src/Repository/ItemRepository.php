<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Doctrine repository coupled to the {@see Item} persistence model.
 *
 * @extends ServiceEntityRepository<Item>
 */
final class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    /**
     * Insert or update the record, keyed by its (application-assigned) id.
     */
    public function save(Item $record): void
    {
        $manager = $this->getEntityManager();
        $existing = $this->find($record->id);

        if ($existing === null) {
            $manager->persist($record);
        } else {
            $existing->userId = $record->userId;
            $existing->name = $record->name;
            $existing->description = $record->description;
            $existing->status = $record->status;
            $existing->imageFilename = $record->imageFilename;
            $existing->imageUrl = $record->imageUrl;
        }

        $manager->flush();
    }

    /**
     * @return Item[]
     */
    public function findByUserId(string $userId): array
    {
        return $this->findBy(['userId' => $userId]);
    }

    /**
     * @param string[] $userIds
     *
     * @return Item[]
     */
    public function findByUserIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return $this->findBy(['userId' => array_values($userIds)]);
    }
}
