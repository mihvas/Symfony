<?php

namespace App\Repository;

use App\Entity\House;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class HouseRepository
{
    private string $filePath;
    private array $header = ['id', 'type', 'beds', 'address', 'price', 'free'];

    public function __construct(ParameterBagInterface $params)
    {
        $this->filePath = $params->get('paths.house_csv');
    }

    public function findAll(): array
    {
        $houses = [];
        if (!file_exists($this->filePath) || !is_readable($this->filePath)) {
            return $houses;
        }

        if (($handle = fopen($this->filePath, 'r')) !== false) {
            $isHeader = true;

            while (($data = fgetcsv($handle)) !== false) {
                if ($isHeader) {
                    $isHeader = false;
                    continue;
                }

                $houses[] = new House(
                    type: (string)$data[1],
                    beds: (int)$data[2],
                    address: (string)$data[3],
                    price: (int)$data[4],
                    free: (bool)$data[5],
                    id: (int)$data[0]
                );
            }

            fclose($handle);
        }
        return $houses;
    }

    public function findAllFree(): array
    {
        $houses = $this->findAll();

        return array_filter($houses, fn(House $house) => $house->getFree());
    }


    public function findById(int $id): ?House
    {
        $houses = $this->findAll();

        foreach ($houses as $house) {
            if ($house->getId() === $id) {
                return $house;
            }
        }
        return null;
    }

    public function save(House $house): int
    {
        $houses = $this->findAll();

        $found = false;

        foreach ($houses as &$existingHouse) {
            if ($existingHouse->getId() === $house->getId()) {
                $existingHouse = $house;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $houses = [$house];
        }

        return $this->writeToFile($houses, $found);
    }

    public function deleteById(int $id): bool
    {
        $houses = $this->findAll();
        if ($this->findById($id) === null) {
            return false;
        }
        $houses = array_filter($houses, fn($house) => $house->getId() !== $id);

        $this->writeToFile($houses, true);
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

    private function writeToFile(array $houses, bool $update = false): int
    {
        $handle = fopen($this->filePath, $update ? 'w' : 'a');
        if ($handle === false) {
            throw new \RuntimeException('Failed to open file: ');
        }
        $id = 0;


        if (!$this->hasHeader()) {
            fputcsv($handle, $this->header);
        }

        if (!$update) {
            $id = $this->getMaxIdFromFile() + 1;
        }


        foreach ($houses as $house) {
            if ($update) {
                $id = $house->getId();
            } else {
                $house->setId($id);
            }

            fputcsv($handle, [
                $id++,
                $house->getType(),
                $house->getBeds(),
                $house->getAddress(),
                $house->getPrice(),
                $house->getFree(),

            ]);
        }
        fclose($handle);
        return $id - 1;
    }


}