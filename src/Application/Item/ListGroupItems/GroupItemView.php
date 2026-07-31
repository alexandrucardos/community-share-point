<?php

declare(strict_types=1);

namespace App\Application\Item\ListGroupItems;

final class GroupItemView
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $status,
        public readonly string $description,
        public readonly string $imageUrl,
        public readonly string $userId,
        public readonly string $contactInfo,
    ) {
    }
}
