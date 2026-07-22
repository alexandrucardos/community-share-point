<?php

declare(strict_types=1);

namespace App\Application\ListUserItems;

final readonly class ListUserItemsDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public string $description,
        public string $imageUrl,
    ) {
    }
}
