<?php

declare(strict_types=1);

namespace App\Application\ListUserItems;

use App\Application\Repository\ItemQueryRepositoryInterface;

final class ListUserItemsHandler
{
    public function __construct(
        private readonly ItemQueryRepositoryInterface $itemRepository,
    ) {
    }

    /**
     * @return ListUserItemsDto[]
     */
    public function handle(ListUserItemsQuery $query): array
    {
        return $this->itemRepository->findAllByUserId($query->userId);
    }
}
