<?php

namespace App\Application\Item\DeleteItem;

use App\Domain\Event\DomainEventPublisherInterface;
use App\Domain\Item\Event\ItemDeletedEvent;
use App\Domain\Item\Exception\ItemAccessDeniedException;
use App\Domain\Item\Exception\ItemNotFoundException;
use App\Domain\Item\ItemRepositoryInterface;

final class DeleteItemHandler
{
    public function __construct(
        private readonly ItemRepositoryInterface       $itemRepository,
        private readonly DomainEventPublisherInterface $domainEventPublisher,
    )
    {
    }

    public function handle(DeleteItemCommand $command): void
    {
        $item = $this->itemRepository->findById($command->itemId);

        if ($item === null) {
            throw new ItemNotFoundException($command->itemId);
        }

        //todo maybe fix with voters in infra
        if ($item->getUserId()->value !== $command->userId) {
            throw new ItemAccessDeniedException();
        }

        $this->itemRepository->delete($item);

        $this->domainEventPublisher->publish(new ItemDeletedEvent(
            itemId: $item->getId()->value,
        ));
    }
}