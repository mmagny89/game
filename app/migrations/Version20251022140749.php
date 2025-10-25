<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251022140749 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE combat (id SERIAL NOT NULL, attacker_id INT NOT NULL, defender_character_id INT DEFAULT NULL, defender_npc_id INT DEFAULT NULL, is_pvp BOOLEAN DEFAULT false NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, winner_character_id INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_8D51E39865F8CAE3 ON combat (attacker_id)');
        $this->addSql('CREATE INDEX IDX_8D51E398202BC9C5 ON combat (defender_character_id)');
        $this->addSql('CREATE INDEX IDX_8D51E3988505C4FE ON combat (defender_npc_id)');
        $this->addSql('CREATE INDEX idx_combat_started ON combat (started_at)');
        $this->addSql('CREATE INDEX idx_combat_pvp ON combat (is_pvp)');
        $this->addSql('COMMENT ON COLUMN combat.started_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN combat.ended_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE combat_turn (id SERIAL NOT NULL, combat_id INT NOT NULL, round INT NOT NULL, attacker_is_npc BOOLEAN DEFAULT false NOT NULL, action VARCHAR(12) NOT NULL, damage INT NOT NULL, attacker_hp INT NOT NULL, defender_hp INT NOT NULL, log_line VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BBEA45DAFC7EEDB8 ON combat_turn (combat_id)');
        $this->addSql('CREATE INDEX idx_turn_round ON combat_turn (round)');
        $this->addSql('CREATE TABLE inventory_item (id SERIAL NOT NULL, character_id INT NOT NULL, item_id INT NOT NULL, equipped BOOLEAN DEFAULT false NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_55BDEA301136BE75 ON inventory_item (character_id)');
        $this->addSql('CREATE INDEX IDX_55BDEA30126F525E ON inventory_item (item_id)');
        $this->addSql('CREATE INDEX idx_inventory_equipped ON inventory_item (equipped)');
        $this->addSql('CREATE TABLE npc (id SERIAL NOT NULL, name VARCHAR(80) NOT NULL, level INT NOT NULL, attack INT NOT NULL, defense INT NOT NULL, health_max INT NOT NULL, exp_reward INT NOT NULL, gold_min INT NOT NULL, gold_max INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE combat ADD CONSTRAINT FK_8D51E39865F8CAE3 FOREIGN KEY (attacker_id) REFERENCES character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE combat ADD CONSTRAINT FK_8D51E398202BC9C5 FOREIGN KEY (defender_character_id) REFERENCES character (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE combat ADD CONSTRAINT FK_8D51E3988505C4FE FOREIGN KEY (defender_npc_id) REFERENCES npc (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE combat_turn ADD CONSTRAINT FK_BBEA45DAFC7EEDB8 FOREIGN KEY (combat_id) REFERENCES combat (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE inventory_item ADD CONSTRAINT FK_55BDEA301136BE75 FOREIGN KEY (character_id) REFERENCES character (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE inventory_item ADD CONSTRAINT FK_55BDEA30126F525E FOREIGN KEY (item_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE combat DROP CONSTRAINT FK_8D51E39865F8CAE3');
        $this->addSql('ALTER TABLE combat DROP CONSTRAINT FK_8D51E398202BC9C5');
        $this->addSql('ALTER TABLE combat DROP CONSTRAINT FK_8D51E3988505C4FE');
        $this->addSql('ALTER TABLE combat_turn DROP CONSTRAINT FK_BBEA45DAFC7EEDB8');
        $this->addSql('ALTER TABLE inventory_item DROP CONSTRAINT FK_55BDEA301136BE75');
        $this->addSql('ALTER TABLE inventory_item DROP CONSTRAINT FK_55BDEA30126F525E');
        $this->addSql('DROP TABLE combat');
        $this->addSql('DROP TABLE combat_turn');
        $this->addSql('DROP TABLE inventory_item');
        $this->addSql('DROP TABLE npc');
    }
}
