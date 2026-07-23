<?php

declare(strict_types=1);

namespace App\Application\ListUserItems;

final class ListUserItemsQuery
{
    public function __construct(
        public readonly string $userId,
    ) {
    }
}
