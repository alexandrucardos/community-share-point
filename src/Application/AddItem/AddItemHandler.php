<?php

declare(strict_types=1);

namespace App\Application\AddItem;

use App\Domain\Item\ItemEntity;
use App\Domain\Item\ItemRepositoryInterface;
use App\Domain\UuidInterface;
use App\Domain\ValueObject\ItemDescriptionValueObject;
use App\Domain\ValueObject\ItemNameValueObject;

final readonly class AddItemHandler
{
    public function __construct(
        private ItemRepositoryInterface $itemRepository,
        private ItemNameValueObject $nameValidator,
        private ItemDescriptionValueObject $descriptionValidator,
        private UuidInterface $uuid,
    ) {
    }

    public function handle(AddItemCommand $command): void
    {
        ($this->nameValidator)($command->name);
        ($this->descriptionValidator)($command->description);

        $item = new ItemEntity($this->uuid->generate());
        $item->setUserId($command->userId);
        $item->setName($command->name);
        $item->setDescription($command->description);
        $item->setStatus($command->status->value);

        $this->itemRepository->add($item);
    }
}
