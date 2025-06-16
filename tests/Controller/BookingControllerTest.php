<?php

namespace App\Tests\Controller;

use App\Entity\Booking;
use App\Entity\House;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingControllerTest extends WebTestCase
{


    public function testCreateBooking(): void
    {
        $client = static::createClient();

        echo "APP_ENV=", getenv('APP_ENV'), "\n";
        echo "DATABASE_URL=", getenv('DATABASE_URL'), "\n";
        $this->assertTrue(true);

        $client->request('POST', '/api/house/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'type' => 'test_booking_house1',
            'beds' => 2,
            'address' => 'address2',
            'price' => 200
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $houseId = $data['houseId'];


        $phone = '79999999999';
        $comment = 'Тестовое бронирование';

        $client->request('POST', '/api/booking/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'phone' => $phone,
            'houseId' => $houseId,
            'comment' => $comment,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $id = $data['bookingId'];

        $em = $client->getContainer()->get('doctrine')->getManager();

        $booking = $em->getRepository(Booking::class)->findOneById($id);

        $this->assertNotNull($booking);
        $this->assertEquals($comment, $booking->getComment());
        $this->assertEquals($phone, $booking->getPhone());
        $this->assertEquals($houseId, $booking->getHouse()->getId());
    }

    public function testUpdateBooking(): void
    {
        $client = static::createClient();


        $client->request('POST', '/api/house/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'type' => 'test_booking_house2',
            'beds' => 2,
            'address' => 'address2',
            'price' => 200
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $houseId = $data['houseId'];

        $client->request('POST', '/api/booking/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'phone' => '78888888888',
            'houseId' => $houseId,
            'comment' => 'Исходный комментарий',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $bookingId = $data['bookingId'];


        $newComment = 'Обновленный комментарий';
        $client->request('PUT', '/api/booking/update', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'id' => $bookingId,
            'comment' => $newComment,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $id = $data['bookingId'];

        $em = $client->getContainer()->get('doctrine')->getManager();

        $updated = $em->getRepository(Booking::class)->findOneById($id);

        $this->assertNotNull($updated);
        $this->assertEquals($newComment, $updated->getComment());
    }

    public function testDeleteBooking(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/house/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'type' => 'test_booking_house3',
            'beds' => 3,
            'address' => 'address3',
            'price' => 300
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $houseId = $data['houseId'];

        $client->request('POST', '/api/booking/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'phone' => '77777777777',
            'houseId' => $houseId,
            'comment' => 'Комментарий на удаление',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $bookingId = $data['bookingId'];


        $client->request('DELETE', '/api/booking/delete', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'id' => $bookingId,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $id = $data['id'];

        $em = $client->getContainer()->get('doctrine')->getManager();

        $deleted = $em->getRepository(Booking::class)->findOneById($id);

        $this->assertNull($deleted);
    }
}
