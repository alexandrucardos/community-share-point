<?php

declare(strict_types=1);

namespace App\Application\Item\ListUserItems;

use App\Application\Item\ItemQueryRepositoryInterface;

final class ListUserItemsHandler
{
    public function __construct(
        private readonly ItemQueryRepositoryInterface $itemRepository,
    ) {
    }

    /**
     * @return UserItemView[]
     */
    public function handle(ListUserItemsQuery $query): array
    {
        return $this->itemRepository->findAllByUserId($query->userId);
    }
}
