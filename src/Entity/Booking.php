<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use OpenApi\Attributes as OA;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'Booking')]
#[OA\Schema(
    schema: 'Booking',
    description: 'Booking entity schema',
    required: ['id', 'phone', 'house'],
    properties: [
        new OA\Property(property: 'id', description: 'Unique identifier', type: 'integer', example: 1),
        new OA\Property(property: 'phone', description: 'Phone number of the person booking', type: 'string', example: '+1234567890'),
        new OA\Property(property: 'house', ref: '#/components/schemas/House'),
        new OA\Property(property: 'comment', description: 'Optional comment', type: 'string', nullable: true, example: 'Near the lake')
    ],
    type: 'object'
)]
class Booking implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    private string $phone;

    #[ORM\ManyToOne(targetEntity: House::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?House $house;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $comment;

    public function __construct(
        string $phone,
        ?string $comment = null,
        ?House $house = null,
        int $id = -1
    ) {
        $this->id = $id;
        $this->house = $house;
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

    public function getHouse(): ?House
    {
        return $this->house;
    }

    public function getComment(): ?string
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

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function setHouse(?House $house): self
    {
        $this->house = $house;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'house' => $this->house instanceof JsonSerializable ? $this->house->jsonSerialize() : null,
            'comment' => $this->comment,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
