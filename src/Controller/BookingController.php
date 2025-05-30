<?php
//
//namespace App\Controller;
//
//use App\Service\BookingService;
//use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use Symfony\Component\HttpFoundation\JsonResponse;
//use Symfony\Component\HttpFoundation\Request;
//use Symfony\Component\Routing\Attribute\Route;
//use function PHPUnit\Framework\isNull;
//
//#[Route('/api/booking', name: 'api_booking_')]
//class BookingController extends AbstractController
//{
//    private BookingService $bookingService;
//
//    public function __construct(BookingService $bookingService)
//    {
//        $this->bookingService = $bookingService;
//    }
//
//    #[Route('/create', name: 'create', methods: ['POST'])]
//    public function createBooking(Request $request): JsonResponse
//    {
//        $data = json_decode($request->getContent(), true);
//
//        $phone = $data['phone'] ?? null;
//        $houseId = $data['houseId'] ?? null;
//        $comment = $data['comment'] ?? '';
//
//
//
//        if (is_null($phone) || is_null($houseId)) {
//            http_response_code(400);
//            return $this->json(['error1' => 'Необходимы параметры phone и houseId']);
//        }
//
//        $id = $this->bookingService->createBooking($phone, $houseId, $comment);
//
//        if (is_null($id)) {
//            http_response_code(400);
//            return $this->json(['error2' => 'Не получилось забронировать домик.
//            Проверьте правильность id и свободность данного домика.']);
//        }
//
//        return $this->json(['success' => true, 'bookingId' => $id]);
//    }
//
//    #[Route('/update', name: 'update', methods: ['PUT'])]
//    public function updateBooking(Request $request): JsonResponse
//    {
//        $data = json_decode($request->getContent(), true);
//
//        $id = $data['id'] ?? null;
//        $newComment = $data['comment'] ?? null;
//
//        if (is_null($id)) {
//            http_response_code(400);
//            return $this->json(['error1' => 'Необходимы параметры id и comment']);
//
//        }
//
//        $booking = $this->bookingService->findBookingById($id);
//        if (!$booking) {
//            http_response_code(404);
//            return $this->json(['error2' => 'Бронирование не найдено']);
//        }
//
//        $booking['comment'] = $newComment;
//
//        $this->bookingService->updateBooking($booking);
//
//        return $this->json(['success' => true, 'message' => 'Комментарий обновлен']);
//    }
//
//
//    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
//    public function deleteBooking(Request $request): JsonResponse
//    {
//        $data = json_decode($request->getContent(), true);
//
//        $id = $data['id'] ?? null;
//
//        if (is_null($id)) {
//            http_response_code(400);
//            return $this->json(['error1' => 'Необходимы параметры id']);
//        }
//
//        $isDelete = $this->bookingService->deleteBooking((int)$id);
//
//        if (!$isDelete) {
//            http_response_code(404);
//            return $this->json(['error2' => 'Не удалось найти запись с таким id']);
//        }
//
//        return $this->json(['success' => true, 'message' => 'Запись удалена']);
//    }
//}

namespace App\Controller;

use App\Entity\Booking;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
        $userId = $data['userId'] ?? null;
        $chadId = $data['chadId'] ?? null;

        if (!$phone || !$houseId) {
            return $this->json(['error' => 'Необходимы параметры phone и houseId'], 400);
        }

        $bookingId = $this->bookingService->createBooking($phone,$houseId, $userId,$chadId, $comment);

        if (!$bookingId) {
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

        if (!$id) {
            return $this->json(['error' => 'Необходим параметр id'], 400);
        }

        $booking = $this->bookingService->findBookingById($id);
        if (!$booking) {
            return $this->json(['error' => 'Бронирование не найдено'], 404);
        }

        $booking['comment'] = $newComment;
        $bookingId = $this->bookingService->updateBooking($booking);
        return $this->json(['success' => true,
            'bookingId' => $bookingId, 'message' => 'Комментарий обновлен']);
    }

    #[Route('/delete', name: 'delete', methods: ['DELETE'])]
    public function deleteBooking(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'] ?? null;

        if (!$id) {
            return $this->json(['error' => 'Необходим параметр id'], 400);
        }

        $deleted = $this->bookingService->deleteBooking((int)$id);

        if (!$deleted) {
            return $this->json(['error' => 'Бронирование с таким id не найдено'], 404);
        }

        return $this->json(['success' => true, 'message' => 'Запись удалена']);
    }
}
