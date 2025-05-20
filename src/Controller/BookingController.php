<?php

namespace App\Controller;

use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/booking', name: 'api_booking_')]
class BookingController extends AbstractController
{
    private BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    #[Route('/create', name: 'create', methods: ['POST'])]
    public function createBooking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $phone = $data['phone'] ?? null;
        $houseId = $data['houseId'] ?? null;
        $comment = $data['comment'] ?? '';

        if (is_null($phone) || is_null($houseId)) {
            http_response_code(400);
            return $this->json(['error1' => 'Необходимы параметры phone и houseId']);
        }

        $id = $this->bookingService->createBooking($phone, $houseId, $comment);

        if (is_null($id)) {
            http_response_code(400);
            return $this->json(['error2' => 'Не получилось забронировать домик. 
            Проверьте правильность id и свободность данного домика.']);
        }

        return $this->json(['success' => true, 'bookingId' => $id]);
    }

    #[Route('/update', name: 'update', methods: ['PUT'])]
    public function updateBooking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $id = $data['id'] ?? null;
        $newComment = $data['comment'] ?? null;

        if (is_null($id)) {
            http_response_code(400);
            return $this->json(['error1' => 'Необходимы параметры id и comment']);

        }

        $booking = $this->bookingService->findBookingById((int)$id);
        if (is_null($booking)) {
            http_response_code(404);
            return $this->json(['error2' => 'Бронирование не найдено']);
        }

        $booking['comment'] = $newComment;

        $this->bookingService->updateBooking($booking);

        return $this->json(['success' => true, 'message' => 'Комментарий обновлен']);
    }


    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function deleteBooking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $id = $data['id'] ?? null;

        if (is_null($id)) {
            http_response_code(400);
            return $this->json(['error1' => 'Необходимы параметры id']);
        }

        $isDelete = $this->bookingService->deleteBooking((int)$id);

        if (!$isDelete) {
            http_response_code(404);
            return $this->json(['error2' => 'Не удалось найти запись с таким id']);
        }

        return $this->json(['success' => true, 'message' => 'Запись удалена']);
    }
}