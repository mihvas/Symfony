<?php

namespace App\Service;

use App\Entity\Booking;
use App\Repository\BookingRepository;

class BookingService
{
    private BookingRepository $repository;

    public function __construct(BookingRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createBooking(string $phone, int $houseId, string $comment): int
    {
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