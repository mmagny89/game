<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class NPC
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private string $name;

    #[ORM\Column]
    private int $level = 1;

    #[ORM\Column]
    private int $attack = 5;

    #[ORM\Column]
    private int $defense = 5;

    #[ORM\Column]
    private int $health_max = 15;

    #[ORM\Column]
    private int $exp_reward = 20;

    #[ORM\Column]
    private int $gold_min = 1;

    #[ORM\Column]
    private int $gold_max = 5;

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getLevel(): int { return $this->level; }
    public function setLevel(int $level): self { $this->level = max(1, $level); return $this; }

    public function getAttack(): int { return $this->attack; }
    public function setAttack(int $v): self { $this->attack = max(0, $v); return $this; }

    public function getDefense(): int { return $this->defense; }
    public function setDefense(int $v): self { $this->defense = max(0, $v); return $this; }

    public function getHealthMax(): int { return $this->health_max; }
    public function setHealthMax(int $v): self { $this->health_max = max(1, $v); return $this; }

    public function getExpReward(): int { return $this->exp_reward; }
    public function setExpReward(int $v): self { $this->exp_reward = max(0, $v); return $this; }

    public function getGoldMin(): int { return $this->gold_min; }
    public function setGoldMin(int $v): self { $this->gold_min = max(0, $v); return $this; }

    public function getGoldMax(): int { return $this->gold_max; }
    public function setGoldMax(int $v): self { $this->gold_max = max($this->gold_min, $v); return $this; }
}
