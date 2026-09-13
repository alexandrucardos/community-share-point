<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Make the response timestamp mandatory for HTTP audit records.
 */
final class Version20260913140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make responded_at mandatory in request_response_log';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('UPDATE request_response_log SET responded_at = CURRENT_TIMESTAMP WHERE responded_at IS NULL');
        $this->rebuildTable(nullable: false);
    }

    public function down(Schema $schema): void
    {
        $this->rebuildTable(nullable: true);
    }

    private function rebuildTable(bool $nullable): void
    {
        $respondedAtDefinition = $nullable ? 'DATETIME DEFAULT NULL' : 'DATETIME NOT NULL';

        $this->addSql('CREATE TEMPORARY TABLE __temp__request_response_log AS SELECT id, method, route, uri, ip, request_payload, response_payload, status, content_type, content_length, duration_ms, responded_at FROM request_response_log');
        $this->addSql('DROP TABLE request_response_log');
        $this->addSql("CREATE TABLE request_response_log (id VARCHAR(255) NOT NULL, method VARCHAR(255) NOT NULL, route VARCHAR(255) DEFAULT NULL, uri CLOB NOT NULL, ip VARCHAR(255) DEFAULT NULL, request_payload CLOB NOT NULL, response_payload CLOB DEFAULT NULL, status INTEGER DEFAULT NULL, content_type VARCHAR(255) DEFAULT NULL, content_length INTEGER DEFAULT NULL, duration_ms INTEGER DEFAULT NULL, responded_at {$respondedAtDefinition}, PRIMARY KEY(id))");
        $this->addSql('INSERT INTO request_response_log (id, method, route, uri, ip, request_payload, response_payload, status, content_type, content_length, duration_ms, responded_at) SELECT id, method, route, uri, ip, request_payload, response_payload, status, content_type, content_length, duration_ms, responded_at FROM __temp__request_response_log');
        $this->addSql('DROP TABLE __temp__request_response_log');
        $this->addSql('CREATE INDEX idx_request_response_log_method ON request_response_log (method)');
        $this->addSql('CREATE INDEX idx_request_response_log_status ON request_response_log (status)');
    }
}
