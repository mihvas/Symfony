<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'house')]
class House
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    #[ORM\Column(type: 'int', nullable: true)]
    private int $beds;

    #[ORM\Column(type: 'string', length: 255)]
    private string $address;

    #[ORM\Column(type: 'int', nullable: true)]
    private int $price;

    public function __construct(
        string $type,
        int $beds,
        string $address,
        int    $price,
        int $id = -1
    )
    {
        $this->id = $id;
        $this->type = $type;
        $this->beds = $beds;
        $this->address = $address;
        $this->price = $price;
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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'beds' => $this->beds,
            'address' => $this->address,
            'price' => $this->price
        ];
    }

}