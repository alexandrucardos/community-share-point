<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * One row per published domain event.
 *
 * Written for logging/audit purposes only — nothing reads it back to drive
 * application state, so the table is append-only (see
 * {@see EventLogRepository::append()}).
 */
#[ORM\Entity(repositoryClass: EventLogRepository::class)]
#[ORM\Table(name: 'event_log')]
#[ORM\Index(name: 'idx_event_log_aggregate', fields: ['aggregateType', 'aggregateId'])]
#[ORM\Index(name: 'idx_event_log_event_name', fields: ['eventName'])]
#[ORM\Index(name: 'idx_event_log_occurred_at', fields: ['occurredAt'])]
#[ORM\Index(name: 'idx_event_log_actor_id', fields: ['actorId'])]
#[ORM\HasLifecycleCallbacks]
class EventLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    public string $id;

    /** Technical event name, e.g. `item.created`. */
    #[ORM\Column(name: 'event_name', type: 'string')]
    public string $eventName;

    /** Aggregate kind, e.g. `item`. */
    #[ORM\Column(name: 'aggregate_type', type: 'string')]
    public string $aggregateType;

    /** Identifier of the aggregate the event belongs to. */
    #[ORM\Column(name: 'aggregate_id', type: 'string')]
    public string $aggregateId;

    /** Scalar snapshot carried by the event. */
    #[ORM\Column(type: 'json')]
    public array $payload = [];

    /** Domain id of the signed-in user, or null outside an authenticated request. */
    #[ORM\Column(name: 'actor_id', type: 'string', nullable: true)]
    public ?string $actorId = null;

    /** When the fact happened. */
    #[ORM\Column(name: 'occurred_at', type: 'datetime_immutable')]
    public \DateTimeImmutable $occurredAt;

    /** When the row was written. */
    #[ORM\Column(name: 'recorded_at', type: 'datetime_immutable')]
    public \DateTimeImmutable $recordedAt;

    #[ORM\PrePersist]
    public function setRecordedAtValue(): void
    {
        $this->recordedAt = new \DateTimeImmutable();
    }
}
