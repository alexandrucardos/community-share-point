<?php

declare(strict_types=1);

namespace App\Application\ListGroupItems;

final readonly class ListGroupItemsQuery
{
    public function __construct(
        public string $groupId,
    ) {
    }
}
