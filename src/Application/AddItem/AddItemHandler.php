<?php

declare(strict_types=1);

namespace App\Application\AddItem;

use App\Domain\Item\ItemEntity;
use App\Domain\Item\ItemRepositoryInterface;
use App\Domain\UuidInterface;
use App\Domain\ValueObject\ItemDescriptionValueObject;
use App\Domain\ValueObject\ItemNameValueObject;
use App\Domain\ValueObject\UuidValueObject;

final class AddItemHandler
{
    public function __construct(
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly ItemNameValueObject $nameValidator,
        private readonly ItemDescriptionValueObject $descriptionValidator,
        private readonly UuidInterface $uuid,
        private readonly UuidValueObject $uuidValueObject,
    ) {
    }

    public function handle(AddItemCommand $command): void
    {
        ($this->nameValidator)($command->name);
        ($this->descriptionValidator)($command->description);

        $item = new ItemEntity($this->uuid->generate());
        $item->setUserId(($this->uuidValueObject)($command->userId));
        $item->setName($command->name);
        $item->setDescription($command->description);
        $item->setStatus($command->status->value);

        if ($command->imageFilename !== null) {
            $item->setImageFilename($command->imageFilename);
        }

        $this->itemRepository->add($item);
    }
}
