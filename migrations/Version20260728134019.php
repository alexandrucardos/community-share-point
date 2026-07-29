<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add the persisted image_url column to the items table.
 */
final class Version20260728134019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image_url column to items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE items ADD COLUMN image_url CLOB DEFAULT \'\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__items AS SELECT id, user_id, name, description, status, image_filename FROM items');
        $this->addSql('DROP TABLE items');
        $this->addSql('CREATE TABLE items (id VARCHAR(255) NOT NULL, user_id VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB NOT NULL, status VARCHAR(255) NOT NULL, image_filename VARCHAR(255) DEFAULT \'\' NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO items (id, user_id, name, description, status, image_filename) SELECT id, user_id, name, description, status, image_filename FROM __temp__items');
        $this->addSql('DROP TABLE __temp__items');
        $this->addSql('CREATE INDEX IDX_E11EE94DA76ED395 ON items (user_id)');
    }
}
