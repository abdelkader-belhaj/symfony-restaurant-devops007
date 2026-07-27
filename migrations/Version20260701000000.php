<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\Migrations\AbstractMigration;

final class Version20260701000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add table status, capacity and reservation columns for MySQL-backed reservations.';
    }

    public function up($schema): void
    {
        $this->addSql("ALTER TABLE `table` ADD status VARCHAR(50) NOT NULL DEFAULT 'available', ADD capacity INT DEFAULT 2, ADD reservation JSON DEFAULT NULL");
    }

    public function down($schema): void
    {
        $this->addSql('ALTER TABLE `table` DROP status, DROP capacity, DROP reservation');
    }
}