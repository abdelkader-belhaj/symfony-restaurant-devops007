<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store one-time discount reward state on users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD discount_active TINYINT(1) NOT NULL DEFAULT 0, ADD discount_used TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP discount_active, DROP discount_used');
    }
}
