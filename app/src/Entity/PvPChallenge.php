<?php
declare(strict_types=1);

namespace App\Entity;

use App\Enum\PvpStatus;
use App\Repository\PvPChallengeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(uniqueConstraints: [
    new ORM\UniqueConstraint(name: 'uniq_pair_status', columns: ['challenger_id','opponent_id','status'])
])]
#[ORM\Entity(repositoryClass: PvPChallengeRepository::class)]
class PvPChallenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Le joueur qui lance le défi */
    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Character $challenger = null;

    /** Le joueur défié */
    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Character $opponent = null;

    /** pending | accepted | declined | done */
    #[ORM\Column(length: 20, enumType: \App\Enum\PvpStatus::class)]
    private PvpStatus $status = PvpStatus::Pending;

    /** ID du Character gagnant (ou null si draw) */
    #[ORM\Column(nullable: true)]
    private ?int $winnerCharacterId = null;

    #[ORM\OneToOne(targetEntity: Combat::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Combat $combat = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Index(columns: ['challenger_id'])]
    #[ORM\Index(columns: ['opponent_id'])]
    #[ORM\Index(columns: ['status'])]

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // --- Getters / Setters ---

    public function getId(): ?int { return $this->id; }

    public function getChallenger(): ?Character { return $this->challenger; }
    public function setChallenger(Character $c): self { $this->challenger = $c; return $this; }

    public function getOpponent(): ?Character { return $this->opponent; }
    public function setOpponent(Character $c): self { $this->opponent = $c; return $this; }

    public function getStatus(): PvpStatus { return $this->status; }
    public function setStatus(PvpStatus $s): self { $this->status = $s; return $this; }

    public function getWinnerCharacterId(): ?int { return $this->winnerCharacterId; }
    public function setWinnerCharacterId(?int $id): self { $this->winnerCharacterId = $id; return $this; }

    public function getCombat(): ?Combat { return $this->combat; }
    public function setCombat(?Combat $combat): self { $this->combat = $combat; return $this; }

    public function getStartedAt(): ?\DateTimeImmutable { return $this->startedAt; }
    public function setStartedAt(?\DateTimeImmutable $d): self { $this->startedAt = $d; return $this; }

    public function getEndedAt(): ?\DateTimeImmutable { return $this->endedAt; }
    public function setEndedAt(?\DateTimeImmutable $d): self { $this->endedAt = $d; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $d): self { $this->createdAt = $d; return $this; }
}
