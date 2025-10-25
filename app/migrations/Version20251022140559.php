<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251022140559 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE character (id SERIAL NOT NULL, user_id INT NOT NULL, name VARCHAR(60) NOT NULL, gender VARCHAR(1) NOT NULL, level INT NOT NULL, exp INT NOT NULL, gold INT NOT NULL, attack_base INT NOT NULL, defense_base INT NOT NULL, health_max INT NOT NULL, health_current INT NOT NULL, last_health_ts TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_937AB034A76ED395 ON character (user_id)');
        $this->addSql('COMMENT ON COLUMN character.last_health_ts IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE item (id SERIAL NOT NULL, name VARCHAR(80) NOT NULL, slot VARCHAR(24) NOT NULL, attack_bonus INT NOT NULL, defense_bonus INT NOT NULL, health_bonus INT NOT NULL, price INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_email ON "user" (email)');
        $this->addSql('ALTER TABLE character ADD CONSTRAINT FK_937AB034A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE character DROP CONSTRAINT FK_937AB034A76ED395');
        $this->addSql('DROP TABLE character');
        $this->addSql('DROP TABLE item');
        $this->addSql('DROP TABLE "user"');
    }
}
