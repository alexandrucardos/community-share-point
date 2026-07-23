<?php

declare(strict_types=1);

namespace App\Application\ListUserItems;

final class ListUserItemsDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $status,
        public readonly string $description,
        public readonly string $imageUrl,
    ) {
    }
}
