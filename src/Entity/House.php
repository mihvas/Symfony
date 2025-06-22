<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\HouseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use OpenApi\Attributes as OA;

#[ORM\Entity(repositoryClass: HouseRepository::class)]
#[ORM\Table(name: 'house')]
#[OA\Schema(
    schema: 'House',
    description: 'House entity schema',
    required: ['id', 'type', 'beds', 'address', 'price', 'free'],
    properties: [
        new OA\Property(property: 'id', description: 'Unique identifier', type: 'integer', example: 1),
        new OA\Property(property: 'type', description: 'Type of house', type: 'string', example: 'Cottage'),
        new OA\Property(property: 'beds', description: 'Number of beds', type: 'integer', example: 3),
        new OA\Property(property: 'address', description: 'Address of the house', type: 'string', example: '123 Main St'),
        new OA\Property(property: 'price', description: 'Price per night', type: 'integer', example: 150),
        new OA\Property(property: 'free', description: 'Whether the house is available', type: 'boolean', example: true)
    ],
    type: 'object'
)]
class House implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    #[ORM\Column(type: 'integer', nullable: true)]
    private int $beds;

    #[ORM\Column(type: 'string', length: 255)]
    private string $address;

    #[ORM\Column(type: 'integer', nullable: true)]
    private int $price;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private bool $free;

    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'house')]
    private Collection $bookings;

    public function __construct(
        string $type,
        int $beds,
        string $address,
        int $price,
        bool $free = true,
        int $id = -1
    ) {
        $this->bookings = new ArrayCollection();
        $this->id = $id;
        $this->type = $type;
        $this->beds = $beds;
        $this->address = $address;
        $this->price = $price;
        $this->free = $free;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getBeds(): int
    {
        return $this->beds;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function getFree(): bool
    {
        return $this->free;
    }

    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function setBeds(int $beds): void
    {
        $this->beds = $beds;
    }

    public function setFree(bool $free): void
    {
        $this->free = $free;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'beds' => $this->beds,
            'address' => $this->address,
            'price' => $this->price,
            'free' => $this->free
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
