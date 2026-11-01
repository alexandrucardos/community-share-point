<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Login is now email + one-time code, so users no longer carry a password.
 */
final class Version20261101130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop users.password now that login uses email one-time codes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP COLUMN password');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL DEFAULT \'\'');
    }
}
