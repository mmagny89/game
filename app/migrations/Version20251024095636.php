<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251024095636 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pv_pchallenge (id SERIAL NOT NULL, challenger_id INT NOT NULL, opponent_id INT NOT NULL, status VARCHAR(20) NOT NULL, winner_character_id INT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_52F82F972D521FDF ON pv_pchallenge (challenger_id)');
        $this->addSql('CREATE INDEX IDX_52F82F977F656CDC ON pv_pchallenge (opponent_id)');
        $this->addSql('COMMENT ON COLUMN pv_pchallenge.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE pv_pchallenge ADD CONSTRAINT FK_52F82F972D521FDF FOREIGN KEY (challenger_id) REFERENCES character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pv_pchallenge ADD CONSTRAINT FK_52F82F977F656CDC FOREIGN KEY (opponent_id) REFERENCES character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE pv_pchallenge DROP CONSTRAINT FK_52F82F972D521FDF');
        $this->addSql('ALTER TABLE pv_pchallenge DROP CONSTRAINT FK_52F82F977F656CDC');
        $this->addSql('DROP TABLE pv_pchallenge');
    }
}
