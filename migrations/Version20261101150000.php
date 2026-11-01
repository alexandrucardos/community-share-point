<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Store PHP sessions in the database via Symfony's PdoSessionHandler.
 *
 * Column names and types follow what PdoSessionHandler expects by default
 * (sess_id / sess_data / sess_lifetime / sess_time).
 */
final class Version20261101150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create sessions table for database-backed PHP sessions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sessions (
            sess_id VARBINARY(128) NOT NULL,
            sess_data BLOB NOT NULL,
            sess_lifetime INTEGER UNSIGNED NOT NULL,
            sess_time INTEGER UNSIGNED NOT NULL,
            PRIMARY KEY(sess_id)
        )');
        $this->addSql('CREATE INDEX sess_lifetime_idx ON sessions (sess_lifetime)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sessions');
    }
}
