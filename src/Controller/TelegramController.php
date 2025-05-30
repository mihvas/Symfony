<?php


namespace App\Controller;

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
    public function webhook(Request $request, LoggerInterface $logger): Response
    {
        $data = json_decode($request->getContent(), true);
        if ($data === null) {
            return new Response('Invalid data', 400);
        }

        $this->handle($data, $logger);

        return new Response('Ok');
    }

    #[Route('/webhook', name: 'telegram_webhook_get', methods: ['GET'])]
    public function webhookGet(): Response
    {
        return new Response('Webhook is active');
    }

    public function handle(array $data, LoggerInterface $logger): void
    {

        if (isset($data['message'])) {
            $message = $data['message'];
            $chatId = $message['chat']['id'];
            $userId = $message['from']['id'];
            $username = $message['from']['username'] ?? 'unknown';
            $text = $message['text'] ?? '';

            $key = 'tg_bot_b' . $userId;
            $text1 = "";
            $bookingId = "";
            $state = $this->cache->getItem($key);
            if ($state->isHit()) {
                $data = $state->get();
                $text1 = $data['action'];
                $bookingId = $data['bookingId'];
            }

            if (str_starts_with($text, '/start')) {
                $this->telegramService->sendWelcome($chatId);
                $this->cache->delete($key);
            } elseif (str_starts_with($text1, 'change_phone')) {
                $this->telegramService->setPhone($text, $chatId, $userId, $bookingId);
            } elseif (str_starts_with($text1, 'change_comment')) {
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

            $this->telegramService->handleBookingRequest($chatId, $userId, $username, $data);
        }
    }
}
