<?php

declare(strict_types=1);

namespace App\Domain\Item;

interface ItemRepositoryInterface
{
    public function add(ItemEntity $item): void;

    public function update(ItemEntity $item): void;

    public function findById(string $id): ?ItemEntity;
}
