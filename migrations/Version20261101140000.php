<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Store uncaught exceptions written by the kernel.exception subscriber.
 *
 * Like event_log this table is append-only: entries are written once and read
 * back only by the error-log page.
 */
final class Version20261101140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create error_log table for uncaught exceptions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE error_log (
            id VARCHAR(36) NOT NULL,
            exception_class VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            code INTEGER DEFAULT NULL,
            file VARCHAR(255) DEFAULT NULL,
            line INTEGER DEFAULT NULL,
            trace TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_error_log_created_at ON error_log (created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE error_log');
    }
}
