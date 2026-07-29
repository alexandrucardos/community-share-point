<?php

declare(strict_types=1);

namespace App\Repository\Adaptor;

use App\Domain\Item\ItemEntity;
use App\Domain\Item\ItemRepositoryInterface;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use App\Entity\Item;
use App\Repository\ItemRepository;

final class ItemRepositoryAdaptor implements ItemRepositoryInterface
{
    public function __construct(
        private readonly ItemRepository $records,
        private readonly ValidatorInterface    $validator,
        private readonly ItemImageResolver     $imageResolver,
    ) {
    }

    public function add(ItemEntity $item): void
    {
        $this->records->save($this->toRecord($item));
    }

    public function update(ItemEntity $item): void
    {
        $this->records->save($this->toRecord($item));
    }

    public function findById(string $id): ?ItemEntity
    {
        $record = $this->records->find($id);

        return $record === null ? null : $this->toDomain($record);
    }

    private function toRecord(ItemEntity $item): Item
    {
        $record = new Item();
        $record->id = $item->getId();
        $record->userId = $item->getUserId()->value;
        $record->name = $item->getName();
        $record->description = $item->getDescription();
        $record->status = $item->getStatus();
        $record->imageFilename = $item->getImageFilename();
        // Resolve and persist the display URL on write so reads can serve it
        // directly; recomputed on every save, so a rename keeps it fresh.
        $record->imageUrl = $this->imageResolver->url($item->getImageFilename(), $item->getName());

        return $record;
    }

    private function toDomain(Item $record): ItemEntity
    {
        $item = new ItemEntity($record->id);

        $item->setUserId((new UuidValueObject($this->validator))($record->userId));
        $item->setName($record->name);
        $item->setDescription($record->description);
        $item->setStatus($record->status);

        $item->setImageFilename($record->imageFilename);
        // Prefer the persisted URL; fall back to resolving it for rows written
        // before the image_url column existed.
        $item->setImageUrl(
            $record->imageUrl !== ''
                ? $record->imageUrl
                : $this->imageResolver->url($record->imageFilename, $record->name)
        );

        return $item;
    }
}
