<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Store issued login codes (hashed) so password login can be replaced by email OTP.
 */
final class Version20261101120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create login_otp table for email one-time login codes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE login_otp (
            id VARCHAR(36) NOT NULL,
            email VARCHAR(180) NOT NULL,
            group_id VARCHAR(36) NOT NULL,
            hashed_code VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            used_at DATETIME DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_login_otp_lookup ON login_otp (email, group_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE login_otp');
    }
}
