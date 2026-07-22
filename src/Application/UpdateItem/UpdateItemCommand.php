<?php

declare(strict_types=1);

namespace App\Application\UpdateItem;

use App\Domain\Item\ItemStatus;

final readonly class UpdateItemCommand
{
    public function __construct(
        public string $itemId,
        public string $userId,
        public string $name,
        public string $description,
        public ItemStatus $status,
        public ?string $imageFilename = null,
    ) {
    }
}
