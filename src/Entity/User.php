<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use LogicException;
use OpenApi\Attributes as OA;
use Override;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_PHONE_NUMBER', fields: ['phone_number'])]
#[OA\Schema(
    schema: 'User',
    description: 'User entity schema',
    required: ['id', 'phone_number', 'roles', 'password'],
    properties: [
        new OA\Property(property: 'id', description: 'Unique identifier', type: 'integer', example: 1),
        new OA\Property(property: 'phone_number', description: 'User phone number (login identifier)', type: 'string', example: '+1234567890'),
        new OA\Property(
            property: 'roles',
            description: 'Array of user roles',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['ROLE_USER', 'ROLE_ADMIN']
        ),
        new OA\Property(property: 'password', description: 'Hashed password', type: 'string', example: '$2y$13$...'),
    ],
    type: 'object'
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface, JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $phone_number = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    public function __construct(
        ?string $phone_number = null,
        array $roles = [],
        ?string $password = null,
        ?int $id = null
    ) {
        $this->id = $id;
        $this->phone_number = $phone_number;
        $this->roles = $roles;
        $this->password = $password;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(string $phone_number): static
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    #[Override]
    public function getUserIdentifier(): string
    {
        if ($this->phone_number === null || $this->phone_number === '') {
            throw new LogicException('User identifier (phone_number) is null or empty');
        }

        return $this->phone_number;
    }

    /**
     * @see UserInterface
     * @return list<string>
     */
    #[Override]
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    #[Override]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    #[Override]
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'phone_number' => $this->phone_number,
            'roles' => $this->getRoles(),
            'password' => $this->password,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
