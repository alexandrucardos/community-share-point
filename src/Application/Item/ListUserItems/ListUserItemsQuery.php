<?php

declare(strict_types=1);

namespace App\Application\Item\ListUserItems;

final class ListUserItemsQuery
{
    public function __construct(
        public readonly string $userId,
    ) {
    }
}
