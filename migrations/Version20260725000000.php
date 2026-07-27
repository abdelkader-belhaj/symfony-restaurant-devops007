<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reservation_status column to table entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `table` ADD reservation_status VARCHAR(20) DEFAULT \'pending\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `table` DROP reservation_status');
    }
}

