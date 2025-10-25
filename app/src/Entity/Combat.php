<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(columns: ['started_at'], name: 'idx_combat_started')]
#[ORM\Index(columns: ['is_pvp'], name: 'idx_combat_pvp')]
class Combat
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    // Attaquant (toujours un Character)
    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Character $attacker = null;

    // Défenseur PVP (facultatif)
    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Character $defender_character = null;

    // Défenseur PNJ (facultatif)
    #[ORM\ManyToOne(targetEntity: NPC::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?NPC $defender_npc = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $is_pvp = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $started_at;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $ended_at = null;

    // Id du gagnant si Character (pour requêtes rapides)
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $winner_character_id = null;

    public function __construct()
    {
        $this->started_at = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getAttacker(): ?Character { return $this->attacker; }
    public function setAttacker(Character $c): self { $this->attacker = $c; return $this; }

    public function getDefenderCharacter(): ?Character { return $this->defender_character; }
    public function setDefenderCharacter(?Character $c): self { $this->defender_character = $c; return $this; }

    public function getDefenderNpc(): ?NPC { return $this->defender_npc; }
    public function setDefenderNpc(?NPC $n): self { $this->defender_npc = $n; return $this; }

    public function isPvp(): bool { return $this->is_pvp; }
    public function setIsPvp(bool $v): self { $this->is_pvp = $v; return $this; }

    public function getStartedAt(): \DateTimeImmutable { return $this->started_at; }
    public function setStartedAt(\DateTimeImmutable $dt): self { $this->started_at = $dt; return $this; }

    public function getEndedAt(): ?\DateTimeImmutable { return $this->ended_at; }
    public function setEndedAt(?\DateTimeImmutable $dt): self { $this->ended_at = $dt; return $this; }

    public function getWinnerCharacterId(): ?int { return $this->winner_character_id; }
    public function setWinnerCharacterId(?int $id): self { $this->winner_character_id = $id; return $this; }
}
