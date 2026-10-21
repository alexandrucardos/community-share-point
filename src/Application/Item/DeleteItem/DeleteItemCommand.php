<?php

namespace App\Application\Item\DeleteItem;

final class DeleteItemCommand
{
    public function __construct(
        public readonly string $itemId,
        public readonly string $userId,
    )
    {
    }
}