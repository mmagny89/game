<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Item
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private string $name;

    /**
     * Slot d’équipement (MVP) : 'weapon' | 'armor' | 'ring' | 'amulet' ...
     */
    #[ORM\Column(length: 24)]
    private string $slot = 'weapon';

    #[ORM\Column] private int $attack_bonus = 0;
    #[ORM\Column] private int $defense_bonus = 0;
    #[ORM\Column] private int $health_bonus = 0;

    // Prix d’achat (en or)
    #[ORM\Column] private int $price = 0;

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getSlot(): string { return $this->slot; }
    public function setSlot(string $slot): self { $this->slot = $slot; return $this; }

    public function getAttackBonus(): int { return $this->attack_bonus; }
    public function setAttackBonus(int $v): self { $this->attack_bonus = max(0, $v); return $this; }

    public function getDefenseBonus(): int { return $this->defense_bonus; }
    public function setDefenseBonus(int $v): self { $this->defense_bonus = max(0, $v); return $this; }

    public function getHealthBonus(): int { return $this->health_bonus; }
    public function setHealthBonus(int $v): self { $this->health_bonus = max(0, $v); return $this; }

    public function getPrice(): int { return $this->price; }
    public function setPrice(int $v): self { $this->price = max(0, $v); return $this; }
}
