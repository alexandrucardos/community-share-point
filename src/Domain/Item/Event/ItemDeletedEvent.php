<?php

namespace App\Domain\Item\Event;

use App\Domain\Event\AbstractDomainEvent;

final class ItemDeletedEvent extends AbstractDomainEvent
{
    public function __construct(
        public readonly string $itemId,
    )
    {
        parent::__construct();
    }

    public function eventName(): string
    {
        return 'item.deleted';
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
        ];
    }
}