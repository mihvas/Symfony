<?php

namespace App\Controller;

use App\Service\HouseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/house', name: 'api_house_')]
class HouseController extends AbstractController
{
    private HouseService $houseService;

    public function __construct(HouseService $houseService)
    {
        $this->houseService = $houseService;
    }

    #[Route('/get', name: 'get', methods: ['GET'])]
    public function getAllHouses(): JsonResponse
    {
        $houses = $this->houseService->getAllHouses();
        return $this->json($houses);
    }

    #[Route('/get_free', name: 'get_free', methods: ['GET'])]
    public function getAllFreeHouses(): JsonResponse
    {
        $freeHouses = $this->houseService->getAllFreeHouses();
        return $this->json($freeHouses);
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function createHouse(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $type = $data['type'] ?? null;
        $beds = $data['beds'] ?? null;
        $address = $data['address'] ?? null;
        $price = $data['price'] ?? null;
        $isFree = $data['free'] ?? true;
        if (is_null($type) || is_null($beds) || is_null($address) || is_null($price)) {
            return $this->json(['error' => 'Необходимы параметры type, beds, address, price'], 400);
        }

        $id = $this->houseService->createHouse($type, $beds, $address, $price,$isFree);

        return $this->json(['success' => true, 'houseId' => $id]);
    }


    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function deleteHouse(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'] ?? null;

        if (is_null($id)) {
            return $this->json(['error' => 'Необходим параметр id'], 400);
        }

        $isDeleted = $this->houseService->deleteHouse((int)$id);

        if (!$isDeleted) {
            return $this->json(['error' => 'Не удалось найти запись с таким id'], 404);
        }

        return $this->json(['success' => true, 'message' => 'Запись удалена']);
    }
}
