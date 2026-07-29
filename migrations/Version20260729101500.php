<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the user_groups table and add the users.group_id foreign key.
 *
 * SQLite cannot add a constraint to an existing table in place, so the users
 * table is rebuilt via the temporary-table dance to attach the FOREIGN KEY.
 */
final class Version20260729101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_groups table and link users.group_id via foreign key';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_groups (id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');

        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, password, contact_info, group_id FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, contact_info VARCHAR(255) NOT NULL, group_id VARCHAR(255) NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_1483A5E9FE54D947 FOREIGN KEY (group_id) REFERENCES user_groups (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO users (id, email, password, contact_info, group_id) SELECT id, email, password, contact_info, group_id FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E9FE54D947 ON users (group_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, email, password, contact_info, group_id FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, contact_info VARCHAR(255) NOT NULL, group_id VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO users (id, email, password, contact_info, group_id) SELECT id, email, password, contact_info, group_id FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E9FE54D947 ON users (group_id)');
        $this->addSql('DROP TABLE user_groups');
    }
}