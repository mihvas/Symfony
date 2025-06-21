<?php


namespace App\Controller;

use App\Entity\Booking;
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
        $comment = $data['comment'] ?? null;

        if ($phone===null || $houseId===null) {
            return $this->json(['error' => 'Необходимы параметры phone и houseId'], 400);
        }

        $bookingId = $this->bookingService->createBooking($phone,$houseId, $comment);

        if ($bookingId===null) {
            return $this->json(['error' => 'Не получилось забронировать домик. Проверьте правильность id и свободность данного домика.'], 400);
        }

        return $this->json([
            'success' => true,
            'bookingId' => $bookingId,
        ]);
    }

    #[Route('/update', name: 'update', methods: ['PUT'])]
    public function updateBooking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

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
            'message' => 'Комментарий обновлен']);
    }

    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function deleteBooking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'] ?? null;

        if ($id===null) {
            return $this->json(['error' => 'Необходим параметр id'], 400);
        }

        $isDeleted = $this->bookingService->deleteBooking((int)$id);

        if (!$isDeleted) {
            return $this->json(['error' => 'Бронирование с таким id не найдено'], 404);
        }

        return $this->json([
            'success' => true,
            'message' => 'Запись удалена']);
    }
}
