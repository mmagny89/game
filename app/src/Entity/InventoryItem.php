<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Index(columns: ['equipped'], name: 'idx_inventory_equipped')]
class InventoryItem
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    // À qui appartient l’objet
    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Character $character = null;

    // Quel item est possédé
    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Item $item = null;

    // Équipé ou non
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $equipped = false;

    public function getId(): ?int { return $this->id; }

    public function getCharacter(): ?Character { return $this->character; }
    public function setCharacter(Character $c): self { $this->character = $c; return $this; }

    public function getItem(): ?Item { return $this->item; }
    public function setItem(Item $i): self { $this->item = $i; return $this; }

    public function isEquipped(): bool { return $this->equipped; }
    public function setEquipped(bool $e): self { $this->equipped = $e; return $this; }
}
