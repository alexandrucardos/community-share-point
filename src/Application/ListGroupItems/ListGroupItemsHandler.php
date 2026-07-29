<?php

declare(strict_types=1);

namespace App\Application\ListGroupItems;

use App\Application\Repository\ItemQueryRepositoryInterface;

final class ListGroupItemsHandler
{
    public function __construct(
        private readonly ItemQueryRepositoryInterface $items,
    ) {
    }

    /**
     * @return ListGroupItemsDto[]
     */
    public function handle(ListGroupItemsQuery $query): array
    {
        return $this->items->findAllForGroup($query->groupId);
    }
}
