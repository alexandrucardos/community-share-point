<?php

declare(strict_types=1);

namespace App\Repository\Adaptor;

use App\Domain\Item\ItemEntity;
use App\Domain\Item\ItemRepositoryInterface;
use App\Domain\ValueObject\UuidValueObject;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Asset\Packages;

final class SqliteItemRepository implements ItemRepositoryInterface
{
    private const COLOR_PALETTE = ['2563eb', 'db2777', 'ea580c', '65a30d', '7c3aed', '0891b2'];
    private const IMAGE_SUBDIRECTORY = 'images/items';

    public function __construct(
        private readonly Connection $connection,
        private readonly Packages $assetPackages,
        private readonly UuidValueObject $uuidValueObject,
    ) {
    }

    public function add(ItemEntity $item): void
    {
        $this->persist($item);
    }

    public function update(ItemEntity $item): void
    {
        $this->persist($item);
    }

    public function findById(string $id): ?ItemEntity
    {
        $record = $this->connection->fetchAssociative(
            'SELECT * FROM items WHERE id = ?',
            [$id],
        );

        return $record === false ? null : $this->mapRecordToItem($record);
    }

    public function findAllByUserId(string $userId): array
    {
        $records = $this->connection->fetchAllAssociative(
            'SELECT * FROM items WHERE user_id = ?',
            [$userId],
        );

        return array_map($this->mapRecordToItem(...), $records);
    }

    public function findAllByUserIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $records = $this->connection->fetchAllAssociative(
            'SELECT * FROM items WHERE user_id IN (?)',
            [array_values($userIds)],
            [ArrayParameterType::STRING],
        );

        return array_map($this->mapRecordToItem(...), $records);
    }

    private function persist(ItemEntity $item): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
                INSERT INTO items (id, user_id, name, description, status, image_filename)
                VALUES (:id, :userId, :name, :description, :status, :imageFilename)
                ON CONFLICT(id) DO UPDATE SET
                    user_id        = excluded.user_id,
                    name           = excluded.name,
                    description    = excluded.description,
                    status         = excluded.status,
                    image_filename = excluded.image_filename
                SQL,
            [
                'id' => $item->getId(),
                'userId' => $item->getUserId()->value,
                'name' => $item->getName(),
                'description' => $item->getDescription(),
                'status' => $item->getStatus(),
                'imageFilename' => $item->getImageFilename(),
            ],
        );
    }

    private function mapRecordToItem(array $record): ItemEntity
    {
        $item = new ItemEntity($record['id']);

        $item->setUserId(($this->uuidValueObject)($record['user_id']));
        $item->setName($record['name']);
        $item->setDescription($record['description']);
        $item->setStatus($record['status']);

        $imageFilename = $record['image_filename'] ?? '';
        $item->setImageFilename($imageFilename);
        $item->setImageUrl(
            $imageFilename !== ''
                ? $this->assetPackages->getUrl(self::IMAGE_SUBDIRECTORY.'/'.$imageFilename)
                : $this->placeholderImage($record['name'])
        );

        return $item;
    }

    private function placeholderImage(string $name): string
    {
        $words = preg_split('/\s+/', trim($name), -1, \PREG_SPLIT_NO_EMPTY);
        $initials = strtoupper(($words[0][0] ?? '').($words[1][0] ?? ''));
        $color = self::COLOR_PALETTE[crc32($name) % count(self::COLOR_PALETTE)];

        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" width="300" height="200" viewBox="0 0 300 200">
                <rect width="300" height="200" fill="#{$color}"/>
                <text x="150" y="112" font-family="sans-serif" font-size="64" fill="#ffffff" text-anchor="middle">{$initials}</text>
            </svg>
            SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
