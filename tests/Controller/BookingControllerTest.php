<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Booking;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingControllerTest extends WebTestCase
{
    private function register(KernelBrowser $client, string $phone): ?string
    {
        $password = 'TestPass123';

        $json = json_encode([
            'phoneNumber' => $phone,
            'password' => $password,
        ]);

        $this->assertNotFalse($json);

        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $json);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);

        $data = json_decode($content, true);
        $this->assertArrayHasKey('token', $data);

        $this->assertNotNull($data['token'] ?? null);

        return $data['token'] ?? null;
    }

    public function testCreateBooking(): void
    {
        $client = static::createClient();
        $phone = '79999999999';

        $token = $this->register($client, $phone);

        $json = json_encode([
            'type' => 'test_booking_house1',
            'beds' => 2,
            'address' => 'address2',
            'price' => 200
        ]);

        $this->assertNotFalse($json);

        $client->request('POST', '/api/house/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
        ], $json);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);

        $data = json_decode($content, true);

        $this->assertArrayHasKey('houseId', $data);
        $houseId = $data['houseId'] ?? null;

        $this->assertNotNull($houseId);

        $comment = 'Тестовое бронирование';

        $json = json_encode([
            'phone' => $phone,
            'houseId' => $houseId,
            'comment' => $comment,
        ]);

        $this->assertNotFalse($json);

        $client->request('POST', '/api/booking/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
        ], $json);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);

        $data = json_decode($content, true);

        $this->assertArrayHasKey('bookingId', $data);
        $id = $data['bookingId'] ?? null;

        $this->assertNotNull($id);

        $em = $client->getContainer()->get('doctrine')->getManager();

        $booking = $em->getRepository(Booking::class)->findById($id);

        $this->assertNotNull($booking);
        $this->assertEquals($comment, $booking->getComment());
        $this->assertEquals($phone, $booking->getPhone());
        $this->assertEquals($houseId, $booking->getHouse()->getId());
    }

    public function testUpdateBooking(): void
    {
        $client = static::createClient();
        $phone = '78888888888';

        $token = $this->register($client, $phone);

        $json = json_encode([
            'type' => 'test_booking_house2',
            'beds' => 2,
            'address' => 'address2',
            'price' => 200
        ]);

        $this->assertNotFalse($json);

        $client->request('POST', '/api/house/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
        ], $json);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);

        $data = json_decode($content, true);

        $this->assertArrayHasKey('houseId', $data);
        $houseId = $data['houseId'] ?? null;

        $this->assertNotNull($houseId);

        $json = json_encode([
            'phone' => $phone,
            'houseId' => $houseId,
            'comment' => 'Исходный комментарий',
        ]);

        $this->assertNotFalse($json);

        $client->request('POST', '/api/booking/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
        ], $json);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);

        $this->assertArrayHasKey('bookingId', $data);
        $bookingId = $data['bookingId'] ?? null;

        $this->assertNotNull($bookingId);

        $newComment = 'Обновленный комментарий';

        $json = json_encode([
            'id' => $bookingId,
            'comment' => $newComment,
        ]);

        $this->assertNotFalse($json);

        $client->request('PUT', '/api/booking/update', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
        ], $json);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);

        $data = json_decode($content, true);

        $this->assertArrayHasKey('bookingId', $data);
        $id = $data['bookingId'] ?? null;

        $this->assertNotNull($id);

        $em = $client->getContainer()->get('doctrine')->getManager();

        $updated = $em->getRepository(Booking::class)->findOneById($id);

        $this->assertNotNull($updated);
        $this->assertEquals($newComment, $updated->getComment());
    }

    public function testDeleteBooking(): void
    {
        $client = static::createClient();
        $phone = '77777777777';

        $token = $this->register($client, $phone);

        $json = json_encode([
            'type' => 'test_booking_house3',
            'beds' => 3,
            'address' => 'address3',
            'price' => 300
        ]);

        $this->assertNotFalse($json);

        $client->request('POST', '/api/house/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
        ], $json);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);

        $data = json_decode($content, true);

        $this->assertArrayHasKey('houseId', $data);
        $houseId = $data['houseId'] ?? null;

        $this->assertNotNull($houseId);

        $json = json_encode([
            'phone' => $phone,
            'houseId' => $houseId,
            'comment' => 'Комментарий на удаление',
        ]);

        $this->assertNotFalse($json);

        $client->request('POST', '/api/booking/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
        ], $json);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);

        $data = json_decode($content, true);

        $this->assertArrayHasKey('bookingId', $data);
        $bookingId = $data['bookingId'] ?? null;

        $this->assertNotNull($bookingId);

        $json = json_encode([
            'id' => $bookingId,
        ]);

        $this->assertNotFalse($json);

        $client->request('DELETE', '/api/booking/delete', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
        ], $json);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);

        $em = $client->getContainer()->get('doctrine')->getManager();

        $deleted = $em->getRepository(Booking::class)->findById($bookingId);

        $this->assertNull($deleted);
    }
}
