<?php

declare(strict_types=1);

namespace App\Application\ListGroupItems;

use App\Domain\Item\ItemEntity;
use App\Domain\Item\ItemRepositoryInterface;
use App\Domain\User\UserEntity;
use App\Domain\User\UserRepositoryInterface;

final class ListGroupItemsHandler
{
    public function __construct(
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @return ListGroupItemsDto[]
     */
    public function handle(ListGroupItemsQuery $query): array
    {
        $groupUsers = $this->userRepository->findAllByGroupId($query->groupId);

        $contactInfoByUserId = array_combine(
            array_map(static fn (UserEntity $user): string => $user->getId(), $groupUsers),
            array_map(static fn (UserEntity $user): string => $user->getContactInfo(), $groupUsers),
        );

        $items = $this->itemRepository->findAllByUserIds(array_keys($contactInfoByUserId));

        return array_map(
            static fn (ItemEntity $item): ListGroupItemsDto => new ListGroupItemsDto(
                id: $item->getId(),
                name: $item->getName(),
                status: $item->getStatus(),
                description: $item->getDescription(),
                imageUrl: $item->getImageUrl(),
                userId: $item->getUserId(),
                contactInfo: $contactInfoByUserId[$item->getUserId()] ?? 'Unknown',
            ),
            $items
        );
    }
}
