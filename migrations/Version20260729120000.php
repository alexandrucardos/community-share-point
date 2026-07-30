<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add a created_at timestamp to every entity table (users, items, user_groups).
 *
 * SQLite's ALTER TABLE ADD COLUMN cannot use CURRENT_TIMESTAMP as a default, so
 * the column is added NOT NULL with a constant sentinel and existing rows are
 * then backfilled to the migration time. New rows get their value from the
 * entities' #[ORM\PrePersist] hook.
 */
final class Version20260729120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at to users, items and user_groups';
    }

    public function up(Schema $schema): void
    {
        foreach (['users', 'items', 'user_groups'] as $table) {
            $this->addSql("ALTER TABLE {$table} ADD COLUMN created_at DATETIME NOT NULL DEFAULT '1970-01-01 00:00:00'");
            $this->addSql("UPDATE {$table} SET created_at = CURRENT_TIMESTAMP");
        }
    }

    public function down(Schema $schema): void
    {
        foreach (['users', 'items', 'user_groups'] as $table) {
            $this->addSql("ALTER TABLE {$table} DROP COLUMN created_at");
        }
    }
}