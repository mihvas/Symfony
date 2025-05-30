<?php


namespace App\Service;

use App\Entity\Booking;
use App\Entity\House;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Telegram\Bot\Api;
use \Cake\Chronos\Chronos;

class TelegramService
{
    private Api $telegram;
    private EntityManagerInterface $em;
    private CacheInterface $cache;

    public function __construct(ParameterBagInterface $params, EntityManagerInterface $em, CacheInterface $cache)
    {
        $token = $params->get('telegram_bot_token');
        $this->telegram = new Api($token);
        $this->em = $em;
        $this->cache = $cache;
    }

    public function safeText(string $text): string
    {
        return mb_detect_encoding($text, 'UTF-8', true)
            ? $text
            : mb_convert_encoding($text, 'UTF-8', 'auto');
    }

    public function sendWelcome(int $chatId): void
    {
        $this->messageBookingList($chatId, "Добро пожаловать! Для выбора домиков введите нужные даты в формате: 2025-06-01 - 2025-06-09.");
    }

    public function isDateRange(string $text): bool
    {
        return preg_match('/\d{4}-\d{2}-\d{2}\s*-\s*\d{4}-\d{2}-\d{2}/', $text);
    }

    public function handleDateRange(int $chatId, string $date, ?House $house = null, int $index = 0): void
    {
        [$start, $end] = array_map('trim', explode(' - ', $date));

        try {
            $dateStart = new Chronos($start);
            $dateEnd = new Chronos($end);

            if ($dateStart->greaterThanOrEquals($dateEnd)) {
                throw new \Exception("Дата начала должна быть раньше даты окончания." . $start);
            }

            $houses = $this->em->getRepository(House::class)->findByAvailablePeriod($dateStart, $dateEnd);

            if (!$houses) {
                $this->messageBookingList($chatId, "Свободных домиков на эти даты нет.");
                return;
            }
            $index1 = $index;
            if (!is_null($house)) {
                foreach ($houses as $i => $h) {
                    if ($h === $house) {
                        $index1 = $i;
                        break;
                    }
                }
            }

            $size = 5;
            $len = count($houses);
            $inlineKeyboard = [];
            $list = intdiv($index1, $size) + 1;
            $text = "Всего домиков {$len}.\nВыберите домик для бронирования:\nСтраница: {$list}.";

            foreach ($houses as $ind => $house) {
                if ($ind < $index1) {
                    continue;
                }
                if ($ind === $size + $index1) {
                    $ind1 = $index + $size;
                    $inlineKeyboard[][] = [
                        'text' => "Следующая страница",
                        'callback_data' => 'next_houses' . $date . '_text_' . $house->getId() . '_' . $ind1
                    ];
                    break;
                }

                $text .= "\n\n";
                $text .= "Домик {$house->getId()}:\n" .
                    "Тип помещения: {$house->getType()}\n" .
                    "Количество спальных мест: {$house->getBeds()}\n" .
                    "Адрес: {$house->getAddress()}\n" .
                    "Цена аренды: {$house->getPrice()} рублей/день";

                $inlineKeyboard[][] = [
                    'text' => "Домик {$house->getId()}",
                    'callback_data' => 'book_' . $house->getId()
                ];


            }

            if ($index !== 0) {
                $start = $index - $size;
                $inlineKeyboard[][] = [
                    'text' => "Прошлая страница",
                    'callback_data' => 'last_houses' . $date . '_text_' . $start
                ];
            }

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                "reply_markup" => json_encode(['inline_keyboard' => $inlineKeyboard]),
            ]);
        } catch (\Exception $e) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "Ошибка: " . $this->safeText($e->getMessage())
            ]);
        }
    }

    public function setPhone(string $phone, int $chatId, int $userId, int $bookingId): void
    {
        $booking = $this->em->getRepository(Booking::class)->find($bookingId);

        if (!$booking) {
            $this->messageBookingList($chatId, "Заявка не найдена.");
            return;
        }
        if (!preg_match('/^\+?\d{10,15}$/', $phone)) {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Неверный формат номера.'
            ]);
            $this->messageBookingActions($chatId, $booking, 'Введите правильный номер:', true);
            return;
        }

        $booking->setPhone($phone);
        $this->em->persist($booking);
        $this->em->flush();

        $key = 'tg_bot_b' . $userId;
        $this->cache->delete($key);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Телефон успешно изменен",
        ]);

        $text = "Заявка {$booking->getId()},\n" .
            "Домик номер: {$booking->getHouse()->getId()},\n" .
            "Номер телефона: " . ($booking->getPhone() ?? 'не указан') . ",\n" .
            "Комментарий:  " . ($booking->getComment() ?? 'не указан') . "\n";
        $text .= "Выберете действие с заявкой:";

        $this->messageBookingActions($chatId, $booking, $text);
    }

    public function setComment(string $comment, int $chatId, int $userId, int $bookingId): void
    {
        $booking = $this->em->getRepository(Booking::class)->find($bookingId);

        if (!$booking) {
            $this->messageBookingList($chatId, "Заявка не найдена.");
            return;
        }

        $comment=mb_substr($comment, 0, 256);
        $booking->setComment($comment);
        $this->em->persist($booking);
        $this->em->flush();

        $key = 'tg_bot_b' . $userId;
        $this->cache->delete($key);

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => "Комментарий успешно изменен",
        ]);

        $text = "Заявка {$booking->getId()},\n" .
            "Домик номер: {$booking->getHouse()->getId()},\n" .
            "Номер телефона: " . ($booking->getPhone() ?? 'не указан') . ",\n" .
            "Комментарий:  " . ($booking->getComment() ?? 'не указан') . "\n";
        $text .= "Выберете действие с заявкой:";

        $this->messageBookingActions($chatId, $booking, $text);
    }

    public function handleBookingRequest(int $chatId, int $userId, string $username, string $callbackData): void
    {
        if (str_starts_with($callbackData, 'book_')) {
            $houseId = (int)str_replace('book_', '', $callbackData);
            $house = $this->em->getRepository(House::class)->find($houseId);

            if (!$house) {
                $this->messageBookingList($chatId, "Домик не найден.");
                return;
            }
            if (!$house->getFree()) {
                $this->messageBookingList($chatId, "Домик в данный момент уже забронирован.");

                return;
            }

            $booking = new Booking(
                telegramUserId: $userId,
                telegramChatId: $chatId,
                house: $house,
            );
            $house->setFree(false);
            $this->em->persist($house);
            $this->em->persist($booking);
            $this->em->flush();

            $this->messageBookingList($chatId, "Заявка на бронирование домика #$houseId успешно отправлена!");
        } else if (str_starts_with($callbackData, 'list_book')) {
            $this->bookingList($chatId, $userId);
        } else if (str_starts_with($callbackData, 'booking_')) {
            $bookingId = (int)str_replace('booking_', '', $callbackData);
            $booking = $this->em->getRepository(Booking::class)->find($bookingId);

            if (!$booking) {
                $this->messageBookingList($chatId, "Заявка не найдена.");
                return;
            }

            $text = "Заявка {$booking->getId()},\n" .
                "Домик номер: {$booking->getHouse()->getId()},\n" .
                "Номер телефона: " . ($booking->getPhone() ?? 'не указан') . ",\n" .
                "Комментарий:  " . ($booking->getComment() ?? 'не указан') . "\n";
            $text .= "Выберете действие с заявкой:";

            $this->messageBookingActions($chatId, $booking, $text);
        } else if (str_starts_with($callbackData, 'change_phone')) {
            $bookingId = (int)str_replace('change_phone', '', $callbackData);
            $booking = $this->em->getRepository(Booking::class)->find($bookingId);

            if (!$booking) {
                $this->messageBookingList($chatId, "Заявка не найдена.");
                return;
            }

            $key = 'tg_bot_b' . $userId;
            $this->cache->delete($key);

            $this->cache->get($key, fn() => ['action' => 'change_phone', 'bookingId' => $bookingId]);

            $text = "Введите свой телефон:";
            $this->messageBookingActions($chatId, $booking, $text, true);
        } else if (str_starts_with($callbackData, 'change_comment')) {
            $bookingId = (int)str_replace('change_comment', '', $callbackData);
            $booking = $this->em->getRepository(Booking::class)->find($bookingId);

            if (!$booking) {
                $this->messageBookingList($chatId, "Заявка не найдена.");
                return;
            }

            $key = 'tg_bot_b' . $userId;
            $this->cache->delete($key);

            $this->cache->get($key, fn() => ['action' => 'change_comment', 'bookingId' => $bookingId]);

            $text = "Введите свой комментарий:";
            $this->messageBookingActions($chatId, $booking, $text, true);
        } else if (str_starts_with($callbackData, 'delete_booking')) {
            $bookingId = (int)str_replace('delete_booking', '', $callbackData);
            $booking = $this->em->getRepository(Booking::class)->find($bookingId);

            if (!$booking) {
                $this->messageBookingList($chatId, "Заявка не найдена.");
                return;
            }
            $booking->getHouse()->setFree(true);
            $this->em->persist($booking->getHouse());
            $this->em->remove($booking);
            $this->em->flush();

            $text = "Заявка удалена";
            $this->messageBookingList($chatId, $text);
        } else if (str_starts_with($callbackData, 'back')) {
            $bookingId = (int)str_replace('back', '', $callbackData);
            $booking = $this->em->getRepository(Booking::class)->find($bookingId);

            $key = 'tg_bot_b' . $userId;
            $this->cache->delete($key);


            if (!$booking) {
                $this->messageBookingList($chatId, "Действие успешно отменено. Заявка не найдена.");
                return;
            }

            $text = "Действие успешно отменено. \n";
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text
            ]);

            $text = "Заявка {$booking->getId()},\n" .
                "Домик номер: {$booking->getHouse()->getId()},\n" .
                "Номер телефона: " . ($booking->getPhone() ?? 'не указан') . ",\n" .
                "Комментарий:  " . ($booking->getComment() ?? 'не указан') . "\n";
            $text .= "Выберете действие с заявкой:";
            $this->messageBookingActions($chatId, $booking, $text);
        } else if (str_starts_with($callbackData, 'next_houses')) {
            $data = preg_replace('/^next_houses/', '', $callbackData);
            $date = preg_replace('/_text_.*$/', '', $data);
            $idIndex = preg_replace('/(^.*_text_)/', '', $data);
            $houseId = (int)preg_replace('/_.*$/', '', $idIndex);
            $index = (int)preg_replace('/^.*_/', '', $idIndex);

            $house = $this->em->getRepository(House::class)->find($houseId);

            $this->handleDateRange($chatId, $date, $house, $index);
        } else if (str_starts_with($callbackData, 'last_houses')) {
            $data = preg_replace('/^last_houses/', '', $callbackData);
            $date = preg_replace('/_text_.*$/', '', $data);
            $index = (int)preg_replace('/(^.*_text_)/', '', $data);

            $this->handleDateRange($chatId, $date, null, $index);
        } else if (str_starts_with($callbackData, 'next_bookings')) {
            $data = preg_replace('/^next_bookings/', '', $callbackData);
            $bookingsId = (int)preg_replace('/_.*$/', '', $data);
            $index = (int)preg_replace('/^.*_/', '', $data);

            $bookings = $this->em->getRepository(Booking::class)->find($bookingsId);

            $this->bookingList($chatId, $userId, $bookings, $index);
        } else if (str_starts_with($callbackData, 'last_bookings')) {
            $index = (int)preg_replace('/^last_bookings/', '', $callbackData);

            $this->bookingList($chatId, $userId, null, $index);
        }


    }

    public function bookingList(int $chatId, int $userId, ?Booking $booking = null, int $index = 0)
    {
        $bookings = $this->em->getRepository(Booking::class)->findAllUser($userId);

        if (!$bookings) {
            $this->messageBookingList($chatId, "Нет заявок о бронировании.");
            return;
        }

        $index1 = $index;
        if (!is_null($booking)) {
            foreach ($bookings as $i => $b) {
                if ($b === $booking) {
                    $index1 = $i;
                    break;
                }
            }
        }

        $size = 5;
        $len = count($bookings);
        $inlineKeyboard = [];
        $list = intdiv($index1, $size) + 1;
        $text = "Всего заявок {$len}.\nВыбере заявку, которую хотите изменить:\nСтраница: {$list}.";

        foreach ($bookings as $ind => $booking) {
            if ($ind < $index1) {
                continue;
            }
            if ($ind === $size + $index1) {
                $ind1 = $index + $size;
                $inlineKeyboard[][] = [
                    'text' => "Следующая страница",
                    'callback_data' => 'next_bookings' . $booking->getId() . '_' . $ind1
                ];
                break;
            }

            $text .= "\n\n";
            $text .= "Заявка {$booking->getId()},\n" .
                "Домик номер: {$booking->getHouse()->getId()},\n" .
                "Номер телефона: " . ($booking->getPhone() ?? 'не указан') . ",\n" .
                "Комментарий:  " . ($booking->getComment() ?? 'не указан') . "\n";
            $inlineKeyboard[][] = [
                'text' => "Заявка {$booking->getId()}",
                'callback_data' => 'booking_' . $booking->getId()
            ];
        }

        if ($index !== 0) {
            $start = $index - $size;
            $inlineKeyboard[][] = [
                'text' => "Прошлая страница",
                'callback_data' => 'last_bookings' . $start
            ];
        }

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $inlineKeyboard])
        ]);

    }

    public function messageBookingActions(int $chatId, Booking $booking, string $text = "Дествия с заявкой:", bool $back = false): void
    {
        $inlineKeyboard = [];

        $inlineKeyboard[][] = [
            'text' => "Изменить телефон",
            'callback_data' => 'change_phone' . $booking->getId()
        ];
        $inlineKeyboard[][] = [
            'text' => "Изменить комментарий",
            'callback_data' => 'change_comment' . $booking->getId()
        ];
        $inlineKeyboard[][] = [
            'text' => "Удалить заявку",
            'callback_data' => 'delete_booking' . $booking->getId()
        ];
        $inlineKeyboard[][] = [
            'text' => "Список бронирование",
            'callback_data' => 'list_book'
        ];

        if ($back) {
            $inlineKeyboard[][] = [
                'text' => "Отменить действие",
                'callback_data' => 'back' . $booking->getId()
            ];
        }

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $inlineKeyboard])
        ]);
    }

    public function messageBookingList(int $chatId, string $text): void
    {
        $inlineKeyboard = [];
        $inlineKeyboard[][] = [
            'text' => "Список бронирование",
            'callback_data' => 'list_book'
        ];

        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode(['inline_keyboard' => $inlineKeyboard])
        ]);
    }

}
