<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260731092641 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop items.image_url column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE items DROP COLUMN image_url');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE items ADD COLUMN image_url CLOB DEFAULT \'\' NOT NULL');
    }
}
