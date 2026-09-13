<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Domain\Event\DomainEventInterface;
use App\Domain\UuidInterface;
use App\Entity\EventLog;
use App\Repository\EventLogRepository;
use App\Service\Security\SecurityUser;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Writes every published domain event to the `event_log` table.
 *
 * The events are recorded for logging/audit purposes only: nothing is read back
 * from the log to change how the application behaves, so the write happens
 * after the aggregate itself was saved and never replaces the write itself.
 * A failure here surfaces instead of being swallowed, so audit gaps are not
 * silent.
 */
final class PersistDomainEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EventLogRepository $eventLogRepository,
        private readonly UuidInterface $uuid,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function onDomainEvent(DomainEventInterface $event): void
    {
        $record = new EventLog();
        $record->id = $this->uuid->generate();
        $record->eventName = $event->eventName();
        $record->aggregateType = $event->aggregateType();
        $record->aggregateId = $event->aggregateId();
        $record->payload = $event->payload();
        $record->actorId = $this->currentActorId();
        $record->occurredAt = $event->occurredAt();

        $this->eventLogRepository->append($record);
    }

    public static function getSubscribedEvents(): array
    {
        return [DomainEventInterface::class => 'onDomainEvent'];
    }

    /**
     * Domain id of the signed-in user; null for requests without a user
     * (for example registration happening before authentication).
     */
    private function currentActorId(): ?string
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        return $user instanceof SecurityUser ? $user->getUserIdentifier() : null;
    }
}
