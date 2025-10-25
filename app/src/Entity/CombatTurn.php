<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(columns: ['round'], name: 'idx_turn_round')]
class CombatTurn
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Combat::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Combat $combat = null;

    // Numéro du round (1,2,3…)
    #[ORM\Column]
    private int $round = 1;

    // Qui attaque lors de ce tour : PNJ/Opposant ou joueur ?
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $attacker_is_npc = false;

    // 'hit' | 'miss' | 'dodge' | 'crit'
    #[ORM\Column(length: 12)]
    private string $action = 'hit';

    // Dégâts infligés ce tour
    #[ORM\Column]
    private int $damage = 0;

    // PV après l’action (pour log)
    #[ORM\Column]
    private int $attacker_hp = 0;

    #[ORM\Column]
    private int $defender_hp = 0;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $attackerName = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $defenderName = null;

    // Ligne de log courte (affichage)
    #[ORM\Column(length: 255)]
    private string $log_line = '';

    public function getId(): ?int { return $this->id; }

    public function getCombat(): ?Combat { return $this->combat; }
    public function setCombat(Combat $c): self { $this->combat = $c; return $this; }

    public function getRound(): int { return $this->round; }
    public function setRound(int $r): self { $this->round = max(1, $r); return $this; }

    public function isAttackerIsNpc(): bool { return $this->attacker_is_npc; }
    public function setAttackerIsNpc(bool $v): self { $this->attacker_is_npc = $v; return $this; }

    public function getAction(): string { return $this->action; }
    public function setAction(string $a): self { $this->action = $a; return $this; }

    public function getDamage(): int { return $this->damage; }
    public function setDamage(int $d): self { $this->damage = max(0, $d); return $this; }

    public function getAttackerHp(): int { return $this->attacker_hp; }
    public function setAttackerHp(int $hp): self { $this->attacker_hp = max(0, $hp); return $this; }

    public function getDefenderHp(): int { return $this->defender_hp; }
    public function setDefenderHp(int $hp): self { $this->defender_hp = max(0, $hp); return $this; }

    public function getAttackerName(): ?string { return $this->attackerName; }
    public function setAttackerName(?string $name): self { $this->attackerName = $name; return $this; }

    public function getDefenderName(): ?string { return $this->defenderName; }
    public function setDefenderName(?string $name): self { $this->defenderName = $name; return $this; }

    public function getLogLine(): string { return $this->log_line; }
    public function setLogLine(string $l): self { $this->log_line = $l; return $this; }
}
