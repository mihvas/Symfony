<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingControllerTest extends WebTestCase
{

    public function testGetAllBookings(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/booking/create', [], [], [], json_encode([
            'phone' => '79998887766',
            'houseId' => 10,
            'comment' => 'Тестовое бронирование',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
        $client->getResponse()->getContent();

        $client->request('GET', '/api/booking/get');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $this->assertIsArray($data);

        foreach ($data as $booking) {
            $this->assertArrayHasKey('id', $booking);
            $this->assertArrayHasKey('phone', $booking);
            $this->assertArrayHasKey('houseId', $booking);
            $this->assertArrayHasKey('comment', $booking);
        }
    }

    public function testCreateBooking(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/booking/create', [], [], [], json_encode([
            'phone' => '79998887766',
            'houseId' => 10,
            'comment' => 'Тестовое бронирование1',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }

    public function testUpdateBooking(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/booking/create', [], [], [], json_encode([
            'phone' => '79998887766',
            'houseId' => 10,
            'comment' => 'Тестовое бронирование2',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $client->request('PUT', '/api/booking/update', [], [], [], json_encode([
            'id' => $data['bookingId'],
            'comment' => 'Обновленный комментарий2'
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }

    public function testDeleteBooking(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/booking/create',[], [], [], json_encode( [
            'phone' => '79998887766',
            'houseId' => 10,
            'comment' => 'Тестовое бронирование3',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $client->request('DELETE', '/api/booking/delete', [], [], [], json_encode([
            'id' => $data['bookingId'],
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }
}