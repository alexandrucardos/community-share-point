<?php

declare(strict_types=1);

namespace App\Service\Event;

use App\Domain\Event\DomainEventInterface;
use App\Domain\Event\DomainEventPublisherInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Publishes domain events through Symfony's event dispatcher.
 *
 * Every event is dispatched under {@see DomainEventInterface} as the event
 * name, so a single subscriber (the persistence one) receives all of them and
 * new event classes need no wiring. Per-event listeners can still be added
 * later by dispatching the concrete class name as well.
 */
final class DomainEventPublisher implements DomainEventPublisherInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function publish(DomainEventInterface ...$events): void
    {
        foreach ($events as $event) {
            $this->dispatcher->dispatch($event, DomainEventInterface::class);
        }
    }
}
