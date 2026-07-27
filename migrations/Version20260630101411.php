<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260630101411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chefs (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, titre VARCHAR(255) NOT NULL, image LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE gallery (id INT AUTO_INCREMENT NOT NULL, image LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE menu (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, image LONGTEXT NOT NULL, type VARCHAR(50) NOT NULL, price NUMERIC(10, 2) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, items JSON NOT NULL, order_type VARCHAR(255) NOT NULL, total_price DOUBLE PRECISION NOT NULL, total_items INT NOT NULL, is_delivery TINYINT(1) NOT NULL, delivery_address VARCHAR(255) DEFAULT NULL, customer_name VARCHAR(255) DEFAULT NULL, customer_phone VARCHAR(20) DEFAULT NULL, table_number INT DEFAULT NULL, created_at DATETIME NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE partties (id INT AUTO_INCREMENT NOT NULL, price INT NOT NULL, titre VARCHAR(255) NOT NULL, debut VARCHAR(255) NOT NULL, ligne1 VARCHAR(255) NOT NULL, ligne2 VARCHAR(255) NOT NULL, ligne3 VARCHAR(255) NOT NULL, final VARCHAR(255) NOT NULL, image LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE specials (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, sous_titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, image LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `table` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, tel INT NOT NULL, date DATE NOT NULL, time TIME NOT NULL, number_people INT NOT NULL, message LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, nom_complete VARCHAR(255) NOT NULL, tel INT NOT NULL, email VARCHAR(255) NOT NULL, pwd VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE chefs');
        $this->addSql('DROP TABLE contact');
        $this->addSql('DROP TABLE gallery');
        $this->addSql('DROP TABLE menu');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE partties');
        $this->addSql('DROP TABLE specials');
        $this->addSql('DROP TABLE `table`');
        $this->addSql('DROP TABLE user');
    }
}
