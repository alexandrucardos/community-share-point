<?php

declare(strict_types=1);

namespace App\Domain\Event;

interface DomainEventInterface
{
    public function eventName(): string;

    public function aggregateType(): string;

    public function aggregateId(): string;

    public function occurredAt(): \DateTimeImmutable;

    /**
     * @return array<string, scalar|null>
     */
    public function payload(): array;
}
