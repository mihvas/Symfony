<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\House;
use App\Entity\User;
use App\Service\HouseService;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/house', name: 'api_house_')]
class HouseController extends AbstractController
{
    public function __construct(private HouseService $houseService)
    {
    }

    #[OA\Get(
        path: '/api/house/get',
        summary: 'Get all houses',
        security: [['bearerAuth' => []]],
        tags: ['House'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of all houses',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'title', type: 'string', example: 'All houses'),
                        new OA\Property(
                            property: 'house',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: House::class))
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'User not authenticated')
        ]
    )]
    #[Route('/get', name: 'get', methods: ['GET'])]
    public function getAllHouses(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'user not authenticated'], 401);
        }

        return $this->json($this->houseService->getAllHouses());
    }

    #[OA\Get(
        path: '/api/house/get_free',
        summary: 'Get all available houses',
        security: [['bearerAuth' => []]],
        tags: ['House'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of available houses',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'title', type: 'string', example: 'Available houses'),
                        new OA\Property(
                            property: 'house',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: House::class))
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'User not authenticated')
        ]
    )]
    #[Route('/get_free', name: 'get_free', methods: ['GET'])]
    public function getAllFreeHouses(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'user not authenticated'], 401);
        }

        return $this->json($this->houseService->getAllFreeHouses());
    }

    #[OA\Post(
        path: '/api/house/create',
        summary: 'Create a new house entry',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'type', type: 'string', example: 'Cottage'),
                    new OA\Property(property: 'beds', type: 'integer', example: 4),
                    new OA\Property(property: 'address', type: 'string', example: '123 Main St'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', example: 2500.00),
                    new OA\Property(property: 'free', type: 'boolean', example: true),
                ],
                type: 'object'
            )
        ),
        tags: ['House'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'House created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'houseId', type: 'integer', example: 1)
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 400, description: 'Missing required parameters'),
            new OA\Response(response: 401, description: 'User not authenticated')
        ]
    )]
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function createHouse(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'user not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON response');
        }

        $type = $data['type'] ?? null;
        $beds = $data['beds'] ?? null;
        $address = $data['address'] ?? null;
        $price = $data['price'] ?? null;
        $isFree = $data['free'] ?? true;

        if ($type === null || $beds === null || $address === null || $price === null) {
            return $this->json(['error' => 'Parameters type, beds, address, and price are required'], 400);
        }

        $id = $this->houseService->createHouse($type, $beds, $address, $price, $isFree);
        return $this->json(['success' => true, 'houseId' => $id]);
    }

    #[OA\Delete(
        path: '/api/house/delete',
        summary: 'Delete a house entry',
        tags: ['House'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 1)
                ],
                required: ['id'],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'House deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'House deleted')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(response: 400, description: 'Missing id parameter'),
            new OA\Response(response: 404, description: 'House not found'),
            new OA\Response(response: 401, description: 'User not authenticated')
        ]
    )]
    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function deleteHouse(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'user not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON response');
        }

        $id = $data['id'] ?? null;
        if ($id === null) {
            return $this->json(['error' => 'Parameter id is required'], 400);
        }

        $deleted = $this->houseService->deleteHouse((int)$id);
        if (!$deleted) {
            return $this->json(['error' => 'No house found with given id'], 404);
        }

        return $this->json(['success' => true, 'message' => 'House deleted']);
    }
}
