<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Store one database record for each main HTTP request and its response.
 *
 * Request and response payloads are stored separately as Doctrine JSON fields;
 * the request subscriber creates the row and the response subscriber completes
 * it using the request id kept in the request attributes.
 */
final class Version20260913120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create request_response_log table with request and response JSON payloads';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE request_response_log (id VARCHAR(255) NOT NULL, method VARCHAR(255) NOT NULL, route VARCHAR(255) DEFAULT NULL, uri CLOB NOT NULL, ip VARCHAR(255) DEFAULT NULL, request_payload CLOB NOT NULL, response_payload CLOB DEFAULT NULL, status INTEGER DEFAULT NULL, content_type VARCHAR(255) DEFAULT NULL, content_length INTEGER DEFAULT NULL, duration_ms INTEGER DEFAULT NULL, requested_at DATETIME NOT NULL, responded_at DATETIME DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_request_response_log_requested_at ON request_response_log (requested_at)');
        $this->addSql('CREATE INDEX idx_request_response_log_method ON request_response_log (method)');
        $this->addSql('CREATE INDEX idx_request_response_log_status ON request_response_log (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE request_response_log');
    }
}
