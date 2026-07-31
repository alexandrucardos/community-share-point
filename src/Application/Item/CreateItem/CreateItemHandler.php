<?php

declare(strict_types=1);

namespace App\Application\Item\CreateItem;

use App\Domain\Item\ItemEntity;
use App\Domain\Item\ItemRepositoryInterface;
use App\Domain\UuidInterface;
use App\Domain\ValueObject\ItemDescriptionValueObject;
use App\Domain\ValueObject\ItemNameValueObject;
use App\Domain\ValueObject\UuidValueObject;
use App\Domain\ValueObject\ValidatorInterface;

final class CreateItemHandler
{
    public function __construct(
        private readonly ItemRepositoryInterface $itemRepository,
        private readonly ItemNameValueObject $nameValidator,
        private readonly ItemDescriptionValueObject $descriptionValidator,
        private readonly UuidInterface $uuid,
        private readonly UuidValueObject $uuidValueObject,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function handle(CreateItemCommand $command): void
    {
        ($this->nameValidator)($command->name);
        ($this->descriptionValidator)($command->description);

        $item = new ItemEntity(
            (new UuidValueObject($this->validator))($this->uuid->generate())
        );

        $item->setUserId(($this->uuidValueObject)($command->userId));
        $item->setName($command->name);
        $item->setDescription($command->description);
        $item->setStatus($command->status->value);

        if ($command->fileInfo !== null) {
            $item->setFileExtension($command->fileInfo->fileExtension);
            $item->setFileName($command->fileInfo->fileName);
            $item->setFileContent($command->fileInfo->fileContent);
            $item->setContainsFile(true);
        }

        $this->itemRepository->add($item);
    }
}
