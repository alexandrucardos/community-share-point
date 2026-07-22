<?php

declare(strict_types=1);

namespace App\Application\ListGroupItems;

final readonly class ListGroupItemsDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $status,
        public string $description,
        public string $imageUrl,
        public string $submittedBy,
    ) {
    }
}
