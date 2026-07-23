<?php

declare(strict_types=1);

namespace App\Application\ListUserItems;

use App\Domain\Item\ItemEntity;
use App\Domain\Item\ItemRepositoryInterface;

final class ListUserItemsHandler
{
    public function __construct(
        private readonly ItemRepositoryInterface $itemRepository,
    ) {
    }

    /**
     * @return ListUserItemsDto[]
     */
    public function handle(ListUserItemsQuery $query): array
    {
        return array_map(
            static fn (ItemEntity $item): ListUserItemsDto => new ListUserItemsDto(
                id: $item->getId(),
                name: $item->getName(),
                status: $item->getStatus(),
                description: $item->getDescription(),
                imageUrl: $item->getImageUrl(),
            ),
            $this->itemRepository->findAllByUserId($query->userId)
        );
    }
}
