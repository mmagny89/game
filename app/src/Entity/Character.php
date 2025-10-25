<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Character
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    // Propriétaire : Character -> User (inversedBy côté User)
    #[ORM\OneToOne(inversedBy: 'character')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 60)]
    private string $name;

    // 'M' ou 'F' (MVP)
    #[ORM\Column(length: 1)]
    private string $gender = 'M';

    #[ORM\Column] private int $level = 1;
    #[ORM\Column] private int $exp = 0;
    #[ORM\Column] private int $gold = 0;

    // --- Stats de base ---
    #[ORM\Column] private int $attack_base = 5;
    #[ORM\Column] private int $defense_base = 5;
    #[ORM\Column] private int $health_max = 20;
    #[ORM\Column] private int $health_current = 20;

    // Pour régénération 1 PV/min
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $last_health_ts;

    public function __construct() { $this->last_health_ts = new \DateTimeImmutable(); }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; if ($user->getCharacter() !== $this) { $user->setCharacter($this); } return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getGender(): string { return $this->gender; }
    public function setGender(string $gender): self { $this->gender = $gender; return $this; }

    public function getLevel(): int { return $this->level; }
    public function setLevel(int $level): self { $this->level = max(1, $level); return $this; }

    public function getExp(): int { return $this->exp; }
    public function setExp(int $exp): self { $this->exp = max(0, $exp); return $this; }

    public function getGold(): int { return $this->gold; }
    public function setGold(int $gold): self { $this->gold = max(0, $gold); return $this; }

    public function getAttackBase(): int { return $this->attack_base; }
    public function setAttackBase(int $v): self { $this->attack_base = max(0, $v); return $this; }

    public function getDefenseBase(): int { return $this->defense_base; }
    public function setDefenseBase(int $v): self { $this->defense_base = max(0, $v); return $this; }

    public function getHealthMax(): int { return $this->health_max; }
    public function setHealthMax(int $v): self
    {
        $this->health_max = max(1, $v);
        if ($this->health_current > $this->health_max) { $this->health_current = $this->health_max; }
        return $this;
    }

    public function getHealthCurrent(): int { return $this->health_current; }
    public function setHealthCurrent(int $v): self
    {
        $this->health_current = max(0, $v);
        return $this;
    }

    public function getLastHealthTs(): \DateTimeImmutable { return $this->last_health_ts; }
    public function setLastHealthTs(\DateTimeImmutable $dt): self { $this->last_health_ts = $dt; return $this; }

    /** Régénération 1 PV/min (ou $perMinute) jusqu’à un cap optionnel (PV max total). */
    public function applyRegen(int $perMinute = 1, ?int $cap = null): void
    {
        $now = new \DateTimeImmutable();
        $elapsed = (int)$now->format('U') - (int)$this->last_health_ts->format('U');
        $minutes = intdiv(max($elapsed, 0), 60);
        if ($minutes <= 0) { return; }

        $targetCap = $cap ?? $this->health_max; // si pas de cap fourni, retombe sur le max “base”
        if ($this->health_current < $targetCap) {
            $this->health_current = min($targetCap, $this->health_current + ($minutes * max(0, $perMinute)));
        }
        $this->last_health_ts = $now;
    }
}
