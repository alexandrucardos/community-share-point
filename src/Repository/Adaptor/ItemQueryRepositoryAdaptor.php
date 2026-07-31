<?php

declare(strict_types=1);

namespace App\Repository\Adaptor;

use App\Application\Item\ItemQueryRepositoryInterface;
use App\Application\Item\ListGroupItems\GroupItemView;
use App\Application\Item\ListUserItems\UserItemView;
use App\Domain\Item\ItemStatus;
use App\Entity\Item;
use App\Repository\ItemRepository;
use App\Repository\UserRepository;
use App\Service\Image\ItemImageResolver;

final class ItemQueryRepositoryAdaptor implements ItemQueryRepositoryInterface
{
    public function __construct(
        private readonly ItemRepository    $items,
        private readonly UserRepository    $users,
        private readonly ItemImageResolver $imageResolver,
    ) {
    }

    public function findAllByUserId(
        string $userId,
    ): array
    {
        return array_map(
            fn (Item $record): UserItemView => new UserItemView(
                id: $record->id,
                name: $record->name,
                status: $record->status,
                description: $record->description,
                imageUrl: $this->imageResolver->url($record->imageFilename, $record->name),
            ),
            $this->items->findByUserId($userId),
        );
    }

    public function findAllForGroup(string $groupId): array
    {
        $contactInfoByUserId = [];
        foreach ($this->users->findByGroupId($groupId) as $user) {
            $contactInfoByUserId[$user->id] = $user->contactInfo;
        }

        $records = array_filter(
            $this->items->findByUserIds(array_keys($contactInfoByUserId)),
            static fn (Item $record): bool => $record->status !== ItemStatus::Deleted->value,
        );

        return array_map(
            fn (Item $record): GroupItemView => new GroupItemView(
                id: $record->id,
                name: $record->name,
                status: $record->status,
                description: $record->description,
                imageUrl: $this->imageResolver->url($record->imageFilename, $record->name),
                userId: $record->userId,
                contactInfo: $contactInfoByUserId[$record->userId] ?? 'Unknown',
            ),
            $records,
        );
    }
}
