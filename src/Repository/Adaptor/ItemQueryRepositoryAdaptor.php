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

final class ItemQueryRepositoryAdaptor implements ItemQueryRepositoryInterface
{
    public function __construct(
        private readonly ItemRepository    $items,
        private readonly UserRepository    $users,
        private readonly string $projectDir,
        private readonly string $imagesBasePath,
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
                imageUrl: $this->imageUrl($record),
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
                imageUrl: $this->imageUrl($record),
                userId: $record->userId,
                contactInfo: $contactInfoByUserId[$record->userId] ?? 'Unknown',
            ),
            $records,
        );
    }

    private function imageUrl(Item $record): string
    {
        return $this->imagesBasePath.'/'.$record->imageFilename;
    }
}
