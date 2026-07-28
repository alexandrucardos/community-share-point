<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the users and items tables backing the file-repository replacement.
 */
final class Version20260728120805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users and items tables';
    }

    public function up(Schema $schema): void
    {
        $users = $schema->createTable('users');
        $users->addColumn('id', 'string');
        $users->addColumn('email', 'string');
        $users->addColumn('password', 'string');
        $users->addColumn('contact_info', 'string');
        $users->addColumn('group_id', 'string');
        $users->setPrimaryKey(['id']);
        $users->addUniqueIndex(['email']);
        $users->addIndex(['group_id']);

        $items = $schema->createTable('items');
        $items->addColumn('id', 'string');
        $items->addColumn('user_id', 'string');
        $items->addColumn('name', 'string');
        $items->addColumn('description', 'text');
        $items->addColumn('status', 'string');
        $items->addColumn('image_filename', 'string', ['default' => '']);
        $items->setPrimaryKey(['id']);
        $items->addIndex(['user_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('items');
        $schema->dropTable('users');
    }
}
