<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260720000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Google OAuth fields to user table and make pwd nullable.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD google_id VARCHAR(255) DEFAULT NULL, ADD provider VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE user MODIFY pwd VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user MODIFY tel INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP google_id, DROP provider');
        $this->addSql('ALTER TABLE user MODIFY pwd VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user MODIFY tel INT NOT NULL');
    }
}
