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

    public function createHouse(string $type, int $beds, string $address, float $price): int
    {
        $house = new House($type, $beds, $address, $price);
        return $this->repository->save($house);
    }

    public function updateHouse(array $data): int
    {
        $house = new House($data['type'], $data['beds'], $data['address'], $data['price'],$data['free']);
        return $this->repository->save($house);
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