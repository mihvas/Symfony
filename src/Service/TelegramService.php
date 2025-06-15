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
    private readonly Api $telegram;

    public function __construct(private readonly ParameterBagInterface  $params,
                                private readonly EntityManagerInterface $em,
                                private readonly CacheInterface         $cache)
    {
        $token = $params->get('telegram_bot_token');
        $this->telegram = new Api($token);
    }

    private function safeText(string $text): string
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

            $houseId = $index;
            if (!is_null($house)) {
                foreach ($houses as $i => $h) {
                    if ($h === $house) {
                        $houseId = $i;
                        break;
                    }
                }
            }

            $size = 5;
            $len = count($houses);
            $inlineKeyboard = [];
            $list = intdiv($houseId, $size) + 1;
            $text = "Всего домиков {$len}.\nВыберите домик для бронирования:\nСтраница: {$list}.";

            foreach ($houses as $id => $house) {
                if ($id < $houseId) {
                    continue;
                }
                if ($id === $size + $houseId) {
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

        $comment = mb_substr($comment, 0, 256);
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

    public function createBooking(int $houseId, int $chatId, int $userId): void
    {
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
    }

    public function showBooking(int $bookingId, int $chatId, int $userId): void
    {
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
    }

    public function changePhone(int $bookingId, int $chatId, int $userId): void
    {
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
    }

    public function changeComment(int $bookingId, int $chatId, int $userId): void
    {
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
    }

    public function deleteBooking(int $bookingId, int $chatId, int $userId): void
    {
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
    }

    public function back(int $bookingId, int $chatId, int $userId): void
    {
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
    }

    public function nextPage(int $id, int $chatId, int $userId, int $index, ?string $date, bool $isHouse = true): void
    {
        if ($isHouse) {
            $house = $this->em->getRepository(House::class)->find($id);
            $this->handleDateRange($chatId, $date, $house, $index);
        } else {
            $bookings = $this->em->getRepository(Booking::class)->find($id);
            $this->bookingList($chatId, $userId, $bookings, $index);
        }
    }
    public function lastPage(int $chatId, int $userId, int $index, ?string $date, bool $isHouse = true): void
    {
        if ($isHouse) {
            $this->handleDateRange($chatId, $date, null, $index);
        } else {
            $this->bookingList($chatId, $userId, null, $index);
        }
    }


    public function bookingList(int $chatId, int $userId, ?Booking $booking = null, int $index = 0): void
    {
        $bookings = $this->em->getRepository(Booking::class)->findAllUser($userId);

        if (!$bookings) {
            $this->messageBookingList($chatId, "Нет заявок о бронировании.");
            return;
        }

        $bookingId = $index;
        if (!is_null($booking)) {
            foreach ($bookings as $i => $b) {
                if ($b === $booking) {
                    $bookingId = $i;
                    break;
                }
            }
        }

        $size = 5;
        $len = count($bookings);
        $inlineKeyboard = [];
        $list = intdiv($bookingId, $size) + 1;
        $text = "Всего заявок {$len}.\nВыбере заявку, которую хотите изменить:\nСтраница: {$list}.";

        foreach ($bookings as $id => $booking) {
            if ($id < $bookingId) {
                continue;
            }
            if ($id === $size + $bookingId) {
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
