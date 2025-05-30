<?php

namespace App\Tests\Controller;

use App\Entity\Booking;
use App\Kernel;
use App\Repository\BookingRepository;
use App\Repository\HouseRepository;
use App\Service\BookingService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Path;

class BookingControllerTest extends WebTestCase
{
    private string $testFilePath;
    private string $testFilePathHouse;
    protected function setUp(): void
    {
        $projectDir = dirname(__DIR__, 2);
        $this->testFilePath = $projectDir . '/tests/data/test_booking.csv';
        $this->testFilePathHouse = $projectDir . '/tests/data/test_house.csv';

        file_put_contents($this->testFilePath, '');
        file_put_contents($this->testFilePathHouse, '');

        $paramsMock = $this->createMock(ParameterBagInterface::class);
        $paramsMock->method('get')
            ->with('paths.booking_csv')
            ->willReturn($this->testFilePath);
        $paramsMock->method('get')
            ->with('paths.house_csv')
            ->willReturn($this->testFilePathHouse);
    }

    public function testCreateBooking(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/house/create',[], [], [], json_encode( [
            'type' => 'test_booking_house',
            'beds' => 2,
            'address' => 'address1',
            'price' => 100
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $this->assertArrayHasKey('success', $data);

        $houseId = $data['houseId'];

        $phone = '79999999999';
        $comment = 'Тестовое бронирование1';

        $client->request('POST', '/api/booking/create', [], [], [], json_encode([
            'phone' => $phone,
            'houseId' => $houseId,
            'comment' => $comment,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);

        $content = file_get_contents($this->testFilePath);
        $this->assertNotFalse($content);

        $this->assertStringContainsString($phone, $content, 'Телефон в файле отсутствует');
        $this->assertStringContainsString($comment, $content, 'Комментарий в файле отсутствует');
        $this->assertStringContainsString((string)$houseId, $content, 'houseId в файле отсутствует');
    }

    public function testUpdateBooking(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/house/create',[], [], [], json_encode( [
            'type' => 'test_booking_house2',
            'beds' => 2,
            'address' => 'address1',
            'price' => 100
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $this->assertArrayHasKey('success', $data);

        $phone = '78888888888';
        $houseId = $data['houseId'];
        $client->request('POST', '/api/booking/create', [], [], [], json_encode([
            'phone' => $phone,
            'houseId' => $houseId,
            'comment' => 'Тестовое бронирование2',
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $this->assertArrayHasKey('success', $data);

        $id = $data['bookingId'];
        $comment = 'Обновленный комментарий2';

        $client->request('PUT', '/api/booking/update', [], [], [], json_encode([
            'id' => $id,
            'comment' => $comment,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);

        $content = file_get_contents($this->testFilePath);
        $this->assertNotFalse($content);

        $this->assertStringContainsString((string)$id, $content, 'Изменился id обновляемых данных в файле');
        $this->assertStringContainsString($phone, $content, 'Телефон в файле отсутствует');
        $this->assertStringContainsString($comment, $content, 'Обновленный комментарий в файле отсутствует');
        $this->assertStringContainsString((string)$houseId, $content, 'houseId в файле отсутствует');
    }

    public function testDeleteBooking(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/house/create',[], [], [], json_encode( [
            'type' => 'test_booking_house3',
            'beds' => 2,
            'address' => 'address1',
            'price' => 100
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $this->assertArrayHasKey('success', $data);

        $phone = '77777777777';
        $houseId = $data['houseId'];
        $comment = 'Тестовое бронирование3';

        $client->request('POST', '/api/booking/create', [], [], [], json_encode([
            'phone' => $phone,
            'houseId' => $houseId,
            'comment' => $comment,
        ]));


        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $this->assertArrayHasKey('success', $data);

        $id = $data['bookingId'];

        $client->request('DELETE', '/api/booking/delete', [], [], [], json_encode([
            'id' => $id,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);

        $content = file_get_contents($this->testFilePath);
        $this->assertNotFalse($content);

        $this->assertStringNotContainsString((string)$id, $content, 'id остался в файле');
        $this->assertStringNotContainsString($phone, $content, 'Телефон остался в файле');
        $this->assertStringNotContainsString($comment, $content, 'Комментарий остался в файле');
        $this->assertStringNotContainsString((string)$houseId, $content, 'houseId остался в файле');
    }
}