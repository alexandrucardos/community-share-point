<?php

declare(strict_types=1);

namespace App\Repository\Adaptor;

use App\Domain\Item\ItemEntity;
use App\Domain\Item\ItemRepositoryInterface;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;
use App\Entity\Item;
use App\Repository\ItemRepository;
use App\Service\Image\ImageUploader;

final class ItemRepositoryAdaptor implements ItemRepositoryInterface
{
    public function __construct(
        private readonly ItemRepository $records,
        private readonly ValidatorInterface    $validator,
        private readonly ImageUploader $imageUploader
    ) {
    }

    public function add(ItemEntity $item): void
    {
        if($item->isContainsFile() === true) {
            $this->imageUploader->upload($item);
        }

        $this->records->save($this->toRecord($item));
    }

    public function update(ItemEntity $item): void
    {
        if($item->isContainsFile() === true) {
            $this->imageUploader->upload($item);
        }

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
        $record->id = $item->getId()->value;
        $record->userId = $item->getUserId()->value;
        $record->name = $item->getName();
        $record->description = $item->getDescription();
        $record->status = $item->getStatus();

        if($item->isContainsFile() === true) {
            $record->imageFilename = $item->getFileName();
        }
        return $record;
    }

    private function toDomain(Item $record): ItemEntity
    {
        $item = new ItemEntity((new UuidValueObject($this->validator))($record->id));

        $item->setUserId((new UuidValueObject($this->validator))($record->userId));
        $item->setName($record->name);
        $item->setDescription($record->description);
        $item->setStatus($record->status);

        $item->setFileName($record->imageFilename);

        return $item;
    }
}
