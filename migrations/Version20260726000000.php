<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260726000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notifications column to user entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD notifications JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP notifications');
    }
}
