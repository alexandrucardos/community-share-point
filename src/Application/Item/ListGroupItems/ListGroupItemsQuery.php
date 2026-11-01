<?php

declare(strict_types=1);

namespace App\Application\Item\ListGroupItems;

final class ListGroupItemsQuery
{
    /**
     * @param string|null $name Case-insensitive item-name filter; null lists every item.
     */
    public function __construct(
        public readonly string $groupId,
        public readonly ?string $name = null,
    ) {
    }
}
