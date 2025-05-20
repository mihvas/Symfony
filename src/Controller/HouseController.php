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

    public function __construct(HouseService $HouseService)
    {
        $this->houseService = $HouseService;
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

        if (is_null($type) || is_null($beds) || is_null($address) || is_null($price)) {
            http_response_code(400);
            return $this->json(['error1' => 'Необходимы параметры type, beds, address, price'], 400);
        }

        $id = $this->houseService->createHouse($type, $beds, $address, $price);

        return $this->json(['success' => true,'houseId' => $id]);
    }

    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function deleteHouse(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $id = $data['id'] ?? null;

        if (is_null($id)) {
            http_response_code(400);
            return $this->json(['error1' => 'Необходимы параметры id']);
        }

        $isDelete= $this->houseService->deleteHouse((int)$id);

        if (!$isDelete) {
            http_response_code(404);
            return $this->json(['error2' => 'Не удалось найти запись с таким id']);
        }

        return $this->json(['success' => true, 'message' => 'Запись удалена']);
    }

}