<?php

declare(strict_types=1);

namespace App\Application\ListGroupItems;

final class ListGroupItemsQuery
{
    public function __construct(
        public readonly string $groupId,
    ) {
    }
}
