<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the append-only event_log table.
 *
 * Domain events are persisted here for logging/audit purposes only: the table
 * is written once per published event and is never updated, deleted or read
 * back to drive application state.
 *
 * The auto-generated diff for this change also proposed rebuilding items,
 * users and user_groups because those tables drifted from their mapping in an
 * earlier, unrelated change; that is deliberately left out of this migration.
 */
final class Version20260913060914 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create event_log table for persisted domain events';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE event_log (id VARCHAR(255) NOT NULL, event_name VARCHAR(255) NOT NULL, aggregate_type VARCHAR(255) NOT NULL, aggregate_id VARCHAR(255) NOT NULL, payload CLOB NOT NULL, actor_id VARCHAR(255) DEFAULT NULL, occurred_at DATETIME NOT NULL, recorded_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_event_log_aggregate ON event_log (aggregate_type, aggregate_id)');
        $this->addSql('CREATE INDEX idx_event_log_event_name ON event_log (event_name)');
        $this->addSql('CREATE INDEX idx_event_log_occurred_at ON event_log (occurred_at)');
        $this->addSql('CREATE INDEX idx_event_log_actor_id ON event_log (actor_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE event_log');
    }
}
