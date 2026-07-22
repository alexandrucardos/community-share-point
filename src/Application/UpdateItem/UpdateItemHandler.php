<?php

declare(strict_types=1);

namespace App\Application\UpdateItem;

use App\Domain\Item\Exception\ItemAccessDeniedException;
use App\Domain\Item\Exception\ItemNotFoundException;
use App\Domain\Item\ItemRepositoryInterface;
use App\Domain\ValueObject\ItemDescriptionValueObject;
use App\Domain\ValueObject\ItemNameValueObject;

final readonly class UpdateItemHandler
{
    public function __construct(
        private ItemRepositoryInterface $itemRepository,
        private ItemNameValueObject $nameValidator,
        private ItemDescriptionValueObject $descriptionValidator,
    ) {
    }

    public function handle(UpdateItemCommand $command): void
    {
        $item = $this->itemRepository->findById($command->itemId);

        if ($item === null) {
            throw new ItemNotFoundException($command->itemId);
        }

        if ($item->getUserId() !== $command->userId) {
            throw new ItemAccessDeniedException();
        }

        ($this->nameValidator)($command->name);
        ($this->descriptionValidator)($command->description);

        $item->setName($command->name);
        $item->setDescription($command->description);
        $item->setStatus($command->status->value);

        $this->itemRepository->update($item);
    }
}
