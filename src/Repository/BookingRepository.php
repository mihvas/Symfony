<?php

namespace App\Repository;

use App\Entity\Booking;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BookingRepository
{
    private string $filePath;
    private array $header = ['id', 'phone', 'houseId', 'comment'];

    public function __construct(ParameterBagInterface $params)
    {
        $this->filePath = $params->get('paths.booking_csv');
    }

    public function findAll(): array
    {
        $bookings = [];
        if (!file_exists($this->filePath) || !is_readable($this->filePath)) {
            return $bookings;
        }

        if (($handle = fopen($this->filePath, 'r')) !== false) {
            $isHeader = true;

            while (($data = fgetcsv($handle)) !== false) {
                if ($isHeader) {
                    $isHeader = false;
                    continue;
                }

                $bookings[] = new Booking(
                    phone: (string)$data[1],
                    houseId: (int)$data[2],
                    comment: (string)$data[3],
                    id: (int)$data[0]
                );
            }
            fclose($handle);
        }

        return $bookings;
    }

    public function findById(int $id): ?Booking
    {
        $bookings = $this->findAll();
        foreach ($bookings as $booking) {
            if ($booking->getId() === $id) {
                return $booking;
            }
        }
        return null;
    }

    public function save(Booking $booking): int
    {
        $bookings = $this->findAll();
        $found = false;

        foreach ($bookings as &$existingBooking) {
            if ($existingBooking->getId() === $booking->getId()) {
                $existingBooking = $booking;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $bookings = [$booking];
        }

        return $this->writeToFile($bookings,$found);
    }

    public function deleteById(int $id): bool
    {
        $bookings = $this->findAll();
        if ($this->findById($id) === null) {
            return false;
        }
        $bookings = array_filter($bookings, fn($booking) => $booking->getId() !== $id);
        $this->writeToFile($bookings,true);
        return true;
    }

    private function getMaxIdFromFile(): int
    {
        if (!file_exists($this->filePath) || filesize($this->filePath) === 0) {
            return -1;
        }

        $handle = fopen($this->filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Не удалось открыть файл: {$this->filePath}");
        }

        $maxId = -1;
        $isHeader = true;

        while (($data = fgetcsv($handle)) !== false) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }

            if (isset($data[0]) && is_numeric($data[0])) {
                $id = (int)$data[0];

                if ($id > $maxId) {
                    $maxId = $id;
                }
            }
        }

        fclose($handle);

        return $maxId;
    }

    private function hasHeader(): bool
    {
        if (!file_exists($this->filePath)) {
            throw new \RuntimeException('Failed to find file: ');
        }

        $handle = fopen($this->filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('Failed to open file: ');
        }

        $firstLine = fgetcsv($handle);
        fclose($handle);

        return $firstLine === $this->header;
    }
    private function writeToFile(array $bookings, bool $update = false): int
    {
        $handle = fopen($this->filePath, $update ? 'w' : 'a');
        if ($handle === false) {
            throw new \RuntimeException('Failed to open file: ');
        }

        $id = 0;

        if (!$this->hasHeader()) {
            fputcsv($handle, $this->header);
        }
        if (!$update){
            $id = $this->getMaxIdFromFile() + 1;
        }

        foreach ($bookings as $booking) {
            if ($update) {
                $id = $booking->getId();
            }
            else {
                $booking->setId($id);
            }

            fputcsv($handle, [
                $id++,
                $booking->getPhone(),
                $booking->getHouseId(),
                $booking->getComment()
            ]);
        }
        fclose($handle);

        return $id-1;
    }
}