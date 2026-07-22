<?php

declare(strict_types=1);

namespace App\Application\AddItem;

use App\Domain\Item\ItemStatus;

final readonly class AddItemCommand
{
    public function __construct(
        public string $userId,
        public string $name,
        public string $description,
        public ItemStatus $status,
    ) {
    }
}
