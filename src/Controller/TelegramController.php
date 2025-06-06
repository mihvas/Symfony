<?php


namespace App\Controller;

use App\Entity\Booking;
use App\Entity\House;
use App\Service\TelegramService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;

#[Route('/api/webhook', name: 'api_webhook_')]
class TelegramController extends AbstractController
{
    private TelegramService $telegramService;
    private CacheInterface $cache;

    public function __construct(TelegramService $telegramService, CacheInterface $cache)
    {
        $this->telegramService = $telegramService;
        $this->cache = $cache;
    }

    #[Route('/webhook', name: 'telegram_webhook', methods: ['POST'])]
    public function webhook(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return new Response('Invalid data', 400);
        }

        $this->handle($data);

        return new Response('Ok');
    }

    #[Route('/webhook', name: 'telegram_webhook_get', methods: ['GET'])]
    public function webhookGet(): Response
    {
        return new Response('Webhook is active');
    }

    public function handle(array $data): void
    {

        if (isset($data['message'])) {
            $message = $data['message'];
            $chatId = $message['chat']['id'];
            $userId = $message['from']['id'];
            $username = $message['from']['username'] ?? 'unknown';
            $text = $message['text'] ?? '';

            $key = 'tg_bot_b' . $userId;
            $action = "";
            $bookingId = "";
            $state = $this->cache->getItem($key);

            if ($state->isHit()) {
                $data = $state->get();
                $action = $data['action'];
                $bookingId = $data['bookingId'];
            }

            if (str_starts_with($text, '/start')) {
                $this->telegramService->sendWelcome($chatId);
                $this->cache->delete($key);
            } elseif (str_starts_with($action, 'change_phone')) {
                $this->telegramService->setPhone($text, $chatId, $userId, $bookingId);
            } elseif (str_starts_with($action, 'change_comment')) {
                $this->telegramService->setComment($text, $chatId, $userId, $bookingId);
            } elseif ($this->telegramService->isDateRange($text)) {
                $this->telegramService->handleDateRange($chatId, $text);
            } else {
                $error = "Запрос неверен";
                $this->telegramService->messageBookingList($chatId, $error);
            }
        } elseif (isset($data['callback_query'])) {
            $callback = $data['callback_query'];
            $chatId = $callback['message']['chat']['id'];
            $userId = $callback['from']['id'];
            $username = $callback['from']['username'] ?? 'unknown';
            $data = $callback['data'];

            $this->handleBookingRequest($chatId, $userId, $username, $data);
        }
    }

    public function handleBookingRequest(int $chatId, int $userId, string $username, string $callbackData): void
    {
        if (str_starts_with($callbackData, 'book_')) {
            $houseId = (int)str_replace('book_', '', $callbackData);
            $this->telegramService->createBooking($houseId, $chatId, $userId);
        } else if (str_starts_with($callbackData, 'list_book')) {
            $this->telegramService->bookingList($chatId, $userId);
        } else if (str_starts_with($callbackData, 'booking_')) {
            $bookingId = (int)str_replace('booking_', '', $callbackData);
            $this->telegramService->showBooking($bookingId, $chatId, $userId);
        } else if (str_starts_with($callbackData, 'change_phone')) {
            $bookingId = (int)str_replace('change_phone', '', $callbackData);
            $this->telegramService->changePhone($bookingId, $chatId, $userId);
        } else if (str_starts_with($callbackData, 'change_comment')) {
            $bookingId = (int)str_replace('change_comment', '', $callbackData);
            $this->telegramService->changeComment($bookingId, $chatId, $userId);
        } else if (str_starts_with($callbackData, 'delete_booking')) {
            $bookingId = (int)str_replace('delete_booking', '', $callbackData);
            $this->telegramService->deleteBooking($bookingId, $chatId, $userId);
        } else if (str_starts_with($callbackData, 'back')) {
            $bookingId = (int)str_replace('back', '', $callbackData);
            $this->telegramService->back($bookingId, $chatId, $userId);
        } else if (str_starts_with($callbackData, 'next_houses')) {
            $data = preg_replace('/^next_houses/', '', $callbackData);
            $date = preg_replace('/_text_.*$/', '', $data);
            $idIndex = preg_replace('/(^.*_text_)/', '', $data);
            $id = (int)preg_replace('/_.*$/', '', $idIndex);
            $index = (int)preg_replace('/^.*_/', '', $idIndex);

            $this->telegramService->nextPage($id, $chatId, $userId, $index, $date, true);
        } else if (str_starts_with($callbackData, 'last_houses')) {
            $data = preg_replace('/^last_houses/', '', $callbackData);
            $date = preg_replace('/_text_.*$/', '', $data);
            $index = (int)preg_replace('/(^.*_text_)/', '', $data);

            $this->telegramService->lastPage($chatId, $userId, $index, $date, true);
        } else if (str_starts_with($callbackData, 'next_bookings')) {
            $data = preg_replace('/^next_bookings/', '', $callbackData);
            $id = (int)preg_replace('/_.*$/', '', $data);
            $index = (int)preg_replace('/^.*_/', '', $data);

            $this->telegramService->nextPage($id, $chatId, $userId, $index, null, false);
        } else if (str_starts_with($callbackData, 'last_bookings')) {
            $index = (int)preg_replace('/^last_bookings/', '', $callbackData);

            $this->telegramService->lastPage($chatId, $userId, $index, null, false);
        }
    }
}
