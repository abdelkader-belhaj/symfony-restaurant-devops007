<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260715000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store one-time roulette gift usage on users.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD roulette_used TINYINT(1) NOT NULL DEFAULT 0, ADD roulette_gift JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP roulette_used, DROP roulette_gift');
    }
}
