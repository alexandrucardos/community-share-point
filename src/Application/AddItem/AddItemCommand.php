<?php

declare(strict_types=1);

namespace App\Application\AddItem;

use App\Domain\Item\ItemStatus;

final class AddItemCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $name,
        public readonly string $description,
        public readonly ItemStatus $status,
        public readonly ?string $imageFilename = null,
    ) {
    }
}
