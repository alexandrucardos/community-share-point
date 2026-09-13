<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Remove the request-start timestamp from HTTP request/response audit records.
 *
 * Request records are created at response time, so the requested_at value was
 * not a reliable request-start timestamp and is no longer stored.
 */
final class Version20260913130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove requested_at from request_response_log';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_request_response_log_requested_at');
        $this->addSql('ALTER TABLE request_response_log DROP COLUMN requested_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE request_response_log ADD COLUMN requested_at DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00'");
        $this->addSql('CREATE INDEX idx_request_response_log_requested_at ON request_response_log (requested_at)');
    }
}
