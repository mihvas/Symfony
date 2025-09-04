<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\BookingService;
use OpenApi\Attributes as OA;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/booking', name: 'api_booking_')]
class BookingController extends AbstractController
{
    public function __construct(private BookingService $bookingService)
    {
    }

    #[OA\Post(
        path: '/api/booking/create',
        summary: 'Create a new booking',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['houseId'],
                properties: [
                    new OA\Property(property: 'houseId', type: 'integer', example: 3),
                    new OA\Property(property: 'comment', type: 'string', example: 'Prefer near the lake', nullable: true),
                ]
            )
        ),
        tags: ['Booking'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Booking created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'bookingId', type: 'integer', example: 12)
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Missing or invalid parameters'),
            new OA\Response(response: 401, description: 'User not authenticated')
        ]
    )]
    #[Route('/create', name: 'create', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'user not authenticated'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON response');
        }

        $phone = $user->getPhoneNumber();
        $houseId = $data['houseId'] ?? null;
        $comment = $data['comment'] ?? null;

        if ($phone === null || $houseId === null) {
            return $this->json(['error' => 'Необходимы параметры phone и houseId'], 400);
        }

        $bookingId = $this->bookingService->createBooking($phone, $houseId, $comment);

        if ($bookingId === null) {
            return $this->json(['error' => 'Не получилось забронировать домик. Проверьте правильность id и свободность данного домика.'], 400);
        }

        return $this->json([
            'success' => true,
            'bookingId' => $bookingId,
        ]);
    }

    #[OA\Put(
        path: '/api/booking/update',
        summary: 'Update a booking comment',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 5),
                    new OA\Property(property: 'comment', type: 'string', example: 'Change to later date', nullable: true),
                ]
            )
        ),
        tags: ['Booking'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Booking updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'bookingId', type: 'integer', example: 5),
                        new OA\Property(property: 'message', type: 'string', example: 'Комментарий обновлен')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Missing id parameter'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 401, description: 'User not authenticated')
        ]
    )]
    #[Route('/update', name: 'update', methods: ['PUT'])]
    public function updateBooking(Request $request): JsonResponse
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
        $newComment = $data['comment'] ?? null;

        if ($id === null) {
            return $this->json(['error' => 'Необходим параметр id'], 400);
        }

        $booking = $this->bookingService->findBookingById($id);
        if ($booking === null) {
            return $this->json(['error' => 'Бронирование не найдено'], 404);
        }

        $booking['comment'] = $newComment;
        $bookingId = $this->bookingService->updateBooking($booking);

        return $this->json([
            'success' => true,
            'bookingId' => $bookingId,
            'message' => 'Комментарий обновлен'
        ]);
    }

    #[OA\Delete(
        path: '/api/booking/delete',
        summary: 'Delete a booking by ID',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 7)
                ]
            )
        ),
        tags: ['Booking'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Booking deleted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Запись удалена')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Missing id parameter'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 401, description: 'User not authenticated')
        ]
    )]
    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function deleteBooking(Request $request): JsonResponse
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
            return $this->json(['error' => 'Необходим параметр id'], 400);
        }

        $isDeleted = $this->bookingService->deleteBooking((int) $id);
        if (!$isDeleted) {
            return $this->json(['error' => 'Бронирование с таким id не найдено'], 404);
        }

        return $this->json([
            'success' => true,
            'message' => 'Запись удалена'
        ]);
    }
}
