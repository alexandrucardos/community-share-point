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
        }

        $manager->flush();
    }

    public function delete(string $id): void
    {
        $record = $this->find($id);

        if ($record === null) {
            return;
        }

        $manager = $this->getEntityManager();
        $manager->remove($record);
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
     * @param string[]    $userIds
     * @param string|null $name    Case-insensitive item-name filter; null/blank lists every item.
     *
     * @return Item[]
     */
    public function findByUserIds(array $userIds, ?string $name = null): array
    {
        if ($userIds === []) {
            return [];
        }

        $builder = $this->createQueryBuilder('item')
            ->andWhere('item.userId IN (:userIds)')
            ->setParameter('userIds', array_values($userIds));

        $name = trim((string) $name);

        if ($name !== '') {
            $builder->andWhere('LOWER(item.name) LIKE :name')
                ->setParameter('name', '%'.mb_strtolower($name).'%');
        }

        return $builder->getQuery()->getResult();
    }
}
