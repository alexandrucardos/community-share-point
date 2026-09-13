<?php

declare(strict_types=1);

namespace App\Domain\Item\Event;

use App\Domain\Event\AbstractDomainEvent;

final class ItemCreatedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly string $itemId,
        public readonly string $userId,
        public readonly string $name,
        public readonly string $description,
        public readonly string $status,
        public readonly ?string $imageFilename,
    ) {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'item.created';
    }

    public function aggregateType(): string
    {
        return 'item';
    }

    public function aggregateId(): string
    {
        return $this->itemId;
    }

    public function payload(): array
    {
        return [
            'item_id' => $this->itemId,
            'user_id' => $this->userId,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'image_filename' => $this->imageFilename,
        ];
    }
}
