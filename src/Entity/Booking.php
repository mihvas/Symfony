<?php

namespace App\Entity;
use App\Repository\BookingRepository;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'Booking')]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    private ?string $phone;

    #[ORM\ManyToOne(targetEntity: House::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?House $house;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $comment;

    #[ORM\Column(type: 'integer', nullable: true)]
    private int $telegramUserId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private int $telegramChatId;
    public function __construct(
        int $telegramUserId,
        int $telegramChatId,
        ?string $comment= null,
        ?string $phone= null,
        ?House $house = null,
        int $id = -1
    )
    {
        $this->id = $id;
        $this->house = $house;
        $this->telegramUserId = $telegramUserId;
        $this->telegramChatId = $telegramChatId;
        $this->comment = $comment;
        $this->phone = $phone;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPhone(): ?string
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

    public function getTelegramUserId(): int
    {
        return $this->telegramUserId;
    }

    public function getTelegramChatId(): int
    {
        return $this->telegramChatId;
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


    public function setHouse(?House $house): self
    {
        $this->house = $house;

        return $this;
    }

    public function setTelegramUserId(int $telegramUserId): void
    {
        $this->telegramUserId = $telegramUserId;
    }

    public function setTelegramChatId(int $telegramChatId): void
    {
        $this->telegramChatId = $telegramChatId;

    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'telegramUserId' => $this->telegramUserId,
            'telegramChatId' => $this->telegramChatId,
            'house' => $this->house,
            'comment' => $this->comment,
        ];
    }


}