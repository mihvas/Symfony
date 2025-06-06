<?php

namespace App\Service;

use App\Entity\House;
use App\Repository\HouseRepository;

class HouseService
{
    private HouseRepository $repository;

    public function __construct(HouseRepository $repository)
    {
        $this->repository = $repository;
    }

    public function createHouse(string $type, int $beds, string $address, float $price, bool $isFree = true): int
    {
        $house = new House($type, $beds, $address, $price, $isFree);
        $this->repository->save($house);
        return $house->getId();
    }

    public function updateHouse(array $data): int
    {
        $house = $this->repository->find($data['id']);

        if (!$house) {
            throw new \InvalidArgumentException("House with ID {$data['id']} not found.");
        }

        $house->setType($data['type']);
        $house->setBeds($data['beds']);
        $house->setAddress($data['address']);
        $house->setPrice($data['price']);
        $house->setFree($data['free'] ?? true);

        $this->repository->save($house);
        return $house->getId();
    }

    public function deleteHouse(int $id): bool
    {
        return $this->repository->deleteById($id);
    }

    public function getAllHouses(): array
    {
        return array_map(fn(House $house) => $house->toArray(), $this->repository->findAll());
    }

    public function getAllFreeHouses(): array
    {
        return array_map(fn(House $house) => $house->toArray(), $this->repository->findAllFree());
    }

    public function findHouseById(int $id): ?array
    {
        $house = $this->repository->findById($id);
        return $house?->toArray();
    }
}
