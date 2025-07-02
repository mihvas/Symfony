<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class BookingService
{
    private BookingRepository $repository;
    private HouseRepository $houseRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        BookingRepository $repository,
        HouseRepository $houseRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->repository = $repository;
        $this->houseRepository = $houseRepository;
        $this->entityManager = $entityManager;
    }

    public function createBooking(string $phone, int $houseId, ?string $comment): ?int
    {
        $house = $this->houseRepository->findById($houseId);

        if (!$house || !$house->getFree()) {
            return null;
        }

        $house->setFree(false);
        $booking = new Booking($phone, $comment);
        $booking->setHouse($house);

        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return $booking->getId();
    }

    public function updateBooking(array $data): ?int
    {
        if (!isset($data['id']) || !isset($data['comment'])) {
            throw new InvalidArgumentException('Missing param');
        }

        $booking = $this->repository->findById($data['id']);

        if (!$booking) {
            return null;
        }

        $booking->setComment($data['comment']);

        $this->entityManager->flush();

        return $booking->getId();
    }

    public function deleteBooking(int $id): bool
    {
        $booking = $this->repository->findById($id);

        if (!$booking) {
            return false;
        }

        $house = $booking->getHouse();
        if ($house) {
            $house->setFree(true);
            $this->entityManager->persist($house);
        }

        $this->entityManager->remove($booking);
        $this->entityManager->flush();

        return true;
    }

    public function getAllBookings(): array
    {
        return array_map(
            fn (Booking $booking) => $booking->toArray(),
            $this->repository->findAll()
        );
    }

    public function findBookingById(int $id): ?array
    {
        $booking = $this->repository->findById($id);
        return $booking?->toArray();
    }
}
