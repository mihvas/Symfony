<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250606091638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE Booking (id SERIAL NOT NULL, house_id INT NOT NULL, phone VARCHAR(15) DEFAULT NULL, comment VARCHAR(255) DEFAULT NULL, telegram_user_id INT DEFAULT NULL, telegram_chat_id INT DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_2FB1D4426BB74515 ON Booking (house_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE house (id SERIAL NOT NULL, type VARCHAR(255) NOT NULL, beds INT DEFAULT NULL, address VARCHAR(255) NOT NULL, price INT DEFAULT NULL, free BOOLEAN DEFAULT NULL, date_start VARCHAR(255) DEFAULT NULL, date_end VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Booking ADD CONSTRAINT FK_2FB1D4426BB74515 FOREIGN KEY (house_id) REFERENCES house (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE Booking DROP CONSTRAINT FK_2FB1D4426BB74515
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE Booking
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE house
        SQL);
    }
}
