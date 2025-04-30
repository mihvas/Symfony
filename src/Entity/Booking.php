<?php

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name: 'Booking')]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 15)]
    private string $phone;

    #[ORM\Column(type: 'integer',nullable: true)]
    private int $houseId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $comment;

    public function __construct(
        string $phone,
        int $houseId,
        string $comment=null,
        int $id = -1
    )
    {
        $this->id = $id;
        $this->houseId = $houseId;
        $this->phone = $phone;
        $this->comment = $comment;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getHouseId(): int
    {
        return $this->houseId;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    public function setHouseId(int $houseId): void
    {
        $this->houseId = $houseId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'houseId' => $this->houseId,
            'comment' => $this->comment,
        ];
    }


}