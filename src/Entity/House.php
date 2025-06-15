<?php

namespace App\Entity;

use App\Repository\HouseRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: HouseRepository::class)]
#[ORM\Table(name: 'house')]
class House
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

    #[ORM\Column(type: 'string', length: 255,nullable: true)]
    private ?string $date_start;
    #[ORM\Column(type: 'string', length: 255,nullable: true)]
    private ?string $date_end;


    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'house')]
    private Collection $bookings;

    public function __construct(
        string $type,
        int $beds,
        string $address,
        int $price,
        bool $free = true,
        ?string $date_start = null,
        ?string $date_end = null,
        int $id = -1
    )
    {
        $this->id = $id;
        $this->type = $type;
        $this->beds = $beds;
        $this->address = $address;
        $this->price = $price;
        $this->free = $free;
        $this->date_start = $date_start;
        $this->date_end = $date_end;
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

    public function getDateStart(): ?string
    {
        return $this->date_start;
    }

    public function getDateEnd(): ?string
    {
        return $this->date_end;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
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

    public function setDateStart(string $date_start): void
    {
        $this->date_start = $date_start;
    }

    public function setDateEnd(string $date_end): void
    {
        $this->date_end = $date_end;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'beds' => $this->beds,
            'address' => $this->address,
            'price' => $this->price,
            'free' => $this->free,
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,

        ];
    }

}