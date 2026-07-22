<?php

declare(strict_types=1);

namespace App\Application\ListUserItems;

final readonly class ListUserItemsQuery
{
    public function __construct(
        public string $userId,
    ) {
    }
}
