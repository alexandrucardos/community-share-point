<?php

declare(strict_types=1);

namespace App\Application\Item\ListGroupItems;

use App\Application\Item\ItemQueryRepositoryInterface;

final class ListGroupItemsHandler
{
    public function __construct(
        private readonly ItemQueryRepositoryInterface $items,
    ) {
    }

    /**
     * @return GroupItemView[]
     */
    public function handle(ListGroupItemsQuery $query): array
    {
        return $this->items->findAllForGroup($query->groupId);
    }
}
