<?php

declare(strict_types=1);

namespace App\Repository\Adaptor;

use App\Domain\Item\ItemEntity;
use App\Domain\Item\ItemRepositoryInterface;
use App\Domain\ValueObject\UuidValueObject;
use Symfony\Component\Asset\Packages;

final class FileItemRepository implements ItemRepositoryInterface
{
    private const COLOR_PALETTE = ['2563eb', 'db2777', 'ea580c', '65a30d', '7c3aed', '0891b2'];
    private const IMAGE_SUBDIRECTORY = 'images/items';

    private readonly string $storagePath;

    public function __construct(
        string $projectDir,
        private readonly Packages $assetPackages,
        private readonly UuidValueObject $uuidValueObject,
    ) {
        $this->storagePath = $projectDir.'/var/data/items.json';
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
        $record = $this->readRecords()[$id] ?? null;

        return $record === null ? null : $this->mapRecordToItem($record);
    }

    public function findAllByUserId(string $userId): array
    {
        $records = array_filter(
            $this->readRecords(),
            static fn (array $record): bool => ($record['userId'] ?? $record['ownerId'] ?? '') === $userId,
        );

        return array_map($this->mapRecordToItem(...), array_values($records));
    }

    public function findAllByUserIds(array $userIds): array
    {
        $records = array_filter(
            $this->readRecords(),
            static fn (array $record): bool => in_array($record['userId'] ?? $record['ownerId'] ?? '', $userIds, true),
        );

        return array_map($this->mapRecordToItem(...), array_values($records));
    }

    private function persist(ItemEntity $item): void
    {
        $records = $this->readRecords();

        $records[$item->getId()] = [
            'id' => $item->getId(),
            'userId' => $item->getUserId()->value,
            'name' => $item->getName(),
            'description' => $item->getDescription(),
            'status' => $item->getStatus(),
            'imageFilename' => $item->getImageFilename(),
        ];

        $this->writeRecords($records);
    }

    private function mapRecordToItem(array $record): ItemEntity
    {
        $item = new ItemEntity($record['id']);

        $item->setUserId(($this->uuidValueObject)($record['userId']));
        $item->setName($record['name']);
        $item->setDescription($record['description']);
        $item->setStatus($record['status']);

        $imageFilename = $record['imageFilename'] ?? '';
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

    private function readRecords(): array
    {
        if (!is_file($this->storagePath)) {
            return [];
        }

        $contents = file_get_contents($this->storagePath);

        return $contents === false || $contents === '' ? [] : json_decode($contents, true, flags: \JSON_THROW_ON_ERROR);
    }

    private function writeRecords(array $records): void
    {
        if (!is_dir(\dirname($this->storagePath))) {
            mkdir(\dirname($this->storagePath), recursive: true);
        }

        file_put_contents(
            $this->storagePath,
            json_encode($records, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT),
            \LOCK_EX
        );
    }
}
