<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Replace the single-column unique index on users.email with a composite
 * unique index on (email, group_id).
 *
 * An email is only unique within a group, so the same address may be
 * registered once per group. Indexes can be swapped in place on SQLite
 * without rebuilding the table.
 */
final class Version20260729110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make users unique per (email, group_id) instead of email alone';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_1483A5E9E7927C74');
        $this->addSql('CREATE UNIQUE INDEX uniq_users_email_group ON users (email, group_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_users_email_group');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }
}
