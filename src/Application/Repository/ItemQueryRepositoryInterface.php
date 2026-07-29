<?php

declare(strict_types=1);

namespace App\Application\Repository;

use App\Application\ListGroupItems\ListGroupItemsDto;
use App\Application\ListUserItems\ListUserItemsDto;

interface ItemQueryRepositoryInterface
{
    /**
     * @return ListUserItemsDto[]
     */
    public function findAllByUserId(
        string $userId,
    ): array;

    /**
     * @return ListGroupItemsDto[]
     */
    public function findAllForGroup(string $groupId): array;
}
