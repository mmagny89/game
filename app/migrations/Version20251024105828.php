<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251024105828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pv_pchallenge ADD combat_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pv_pchallenge ADD CONSTRAINT FK_52F82F97FC7EEDB8 FOREIGN KEY (combat_id) REFERENCES combat (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_52F82F97FC7EEDB8 ON pv_pchallenge (combat_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE pv_pchallenge DROP CONSTRAINT FK_52F82F97FC7EEDB8');
        $this->addSql('DROP INDEX UNIQ_52F82F97FC7EEDB8');
        $this->addSql('ALTER TABLE pv_pchallenge DROP combat_id');
    }
}
