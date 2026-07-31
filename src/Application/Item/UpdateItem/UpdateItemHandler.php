<?php

declare(strict_types=1);

namespace App\Application\Item\UpdateItem;

use App\Domain\Item\Exception\ItemAccessDeniedException;
use App\Domain\Item\Exception\ItemNotFoundException;
use App\Domain\Item\ItemRepositoryInterface;
use App\Domain\ValueObject\ItemDescriptionValueObject;
use App\Domain\ValueObject\ItemNameValueObject;

final class UpdateItemHandler
{
    public function __construct(
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly ItemNameValueObject $nameValidator,
        private readonly ItemDescriptionValueObject $descriptionValidator,
    ) {
    }

    public function handle(UpdateItemCommand $command): void
    {
        $item = $this->itemRepository->findById($command->itemId);

        if ($item === null) {
            throw new ItemNotFoundException($command->itemId);
        }

        //todo maybe fix with voters in infra
        if ($item->getUserId()->value !== $command->userId) {
            throw new ItemAccessDeniedException();
        }

        //todo are this necessary ?
        ($this->nameValidator)($command->name);
        ($this->descriptionValidator)($command->description);

        $item->setName($command->name);
        $item->setDescription($command->description);
        $item->setStatus($command->status->value);

        if ($command->fileInfo !== null) {
            $item->setFileExtension($command->fileInfo->fileExtension);
            $item->setFileName($command->fileInfo->fileName);
            $item->setFileContent($command->fileInfo->fileName);
            $item->setContainsFile(true);
        }

        $this->itemRepository->update($item);
    }
}
