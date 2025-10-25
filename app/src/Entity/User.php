<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private string $password;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Character::class, cascade: ['persist', 'remove'])]
    private ?Character $character = null;

    public function getId(): ?int { return $this->id; }

    // --- Email / Identifiant ---
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = strtolower($email); return $this; }
    public function getUserIdentifier(): string { return $this->email; }

    // --- Rôles ---
    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        if (!in_array('ROLE_USER', $roles, true)) { $roles[] = 'ROLE_USER'; }
        return array_values(array_unique($roles));
    }
    /** @param list<string> $roles */
    public function setRoles(array $roles): self { $this->roles = array_values(array_unique($roles)); return $this; }

    // --- Mot de passe (hashé) ---
    public function getPassword(): string { return $this->password; }
    public function setPassword(string $hashedPassword): self { $this->password = $hashedPassword; return $this; }
    public function eraseCredentials(): void {}

    // --- Lien Personnage ---
    public function getCharacter(): ?Character { return $this->character; }
    public function setCharacter(?Character $character): self
    {
        if ($character && $character->getUser() !== $this) { $character->setUser($this); }
        $this->character = $character; return $this;
    }
}
