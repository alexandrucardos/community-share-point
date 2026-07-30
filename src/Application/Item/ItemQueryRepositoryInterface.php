<?php

declare(strict_types=1);

namespace App\Application\Item;

use App\Application\Item\ListGroupItems\GroupItemView;
use App\Application\Item\ListUserItems\UserItemView;

interface ItemQueryRepositoryInterface
{
    /**
     * @return UserItemView[]
     */
    public function findAllByUserId(
        string $userId,
    ): array;

    /**
     * @return GroupItemView[]
     */
    public function findAllForGroup(string $groupId): array;
}
