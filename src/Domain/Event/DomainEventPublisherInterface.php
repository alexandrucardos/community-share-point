<?php

declare(strict_types=1);

namespace App\Domain\Event;

interface DomainEventPublisherInterface
{
    public function publish(DomainEventInterface ...$events): void;
}
