<?php

declare(strict_types=1);

namespace App\Application\ListGroupItems;

use App\Domain\Item\ItemEntity;
use App\Domain\Item\ItemRepositoryInterface;
use App\Domain\User\UserEntity;
use App\Domain\User\UserRepositoryInterface;
use App\Domain\ValueObject\EmailValueObject;

final readonly class ListGroupItemsHandler
{
    public function __construct(
        private ItemRepositoryInterface $itemRepository,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    /**
     * @return ListGroupItemsDto[]
     */
    public function handle(ListGroupItemsQuery $query): array
    {
        $groupUsers = $this->userRepository->findAllByGroupId($query->groupId);

        $emailsByUserId = array_combine(
            array_map(static fn (UserEntity $user): string => $user->getId(), $groupUsers),
            array_map(static fn (UserEntity $user): EmailValueObject => $user->getEmail(), $groupUsers),
        );

        $items = $this->itemRepository->findAllByUserIds(array_keys($emailsByUserId));

        return array_map(
            static fn (ItemEntity $item): ListGroupItemsDto => new ListGroupItemsDto(
                id: $item->getId(),
                name: $item->getName(),
                status: $item->getStatus(),
                description: $item->getDescription(),
                imageUrl: $item->getImageUrl(),
                submittedBy: (string) (($emailsByUserId[$item->getUserId()] ?? null)?->value ?? 'Unknown'),
            ),
            $items
        );
    }
}
