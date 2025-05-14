<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;

class BookingService
{
    private BookingRepository $repository;
    private HouseRepository $houseRepository;
    public function __construct(BookingRepository $repository, HouseRepository $houseRepository)
    {
        $this->repository = $repository;
        $this->houseRepository = $houseRepository;
    }

    public function createBooking(string $phone, int $houseId, string $comment): int|null
    {
        $house = $this->houseRepository->findById($houseId);

        if (!$house || !$house->getFree()) {
            return null;
        }

        $house->setFree(false);
        $this->houseRepository->save($house);

        $booking = new Booking($phone, $houseId, $comment);
        return $this->repository->save($booking);
    }

    public function updateBooking(array $data): int
    {
        $booking = new Booking($data['phone'], $data['houseId'], $data['comment']);
        return $this->repository->save($booking);
    }

    public function deleteBooking(int $id): bool
    {
        $booking = $this->repository->findById($id);
        $house = $this->houseRepository->findById($booking->getHouseId());

        if ($house) {
            $house->setFree(true);
            $this->houseRepository->save($house);
        }

        return $this->repository->deleteById($id);
    }

    public function getAllBookings(): array
    {
        return array_map(fn(Booking $booking) => $booking->toArray(), $this->repository->findAll());
    }

    public function findBookingById(int $id): ?array
    {
        $booking = $this->repository->findById($id);
        return $booking?->toArray();
    }
}