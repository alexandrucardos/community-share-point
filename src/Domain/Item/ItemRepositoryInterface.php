<?php

declare(strict_types=1);

namespace App\Domain\Item;

interface ItemRepositoryInterface
{
    public function add(ItemEntity $item): void;

    public function update(ItemEntity $item): void;

    public function findById(string $id): ?ItemEntity;

    /**
     * @return ItemEntity[]
     */
    public function findAllByUserId(string $userId): array;

    /**
     * @param string[] $userIds
     *
     * @return ItemEntity[]
     */
    public function findAllByUserIds(array $userIds): array;
}
