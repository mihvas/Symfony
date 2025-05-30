<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


class HouseControllerTest extends WebTestCase
{
    private string $testFilePath;

    protected function setUp(): void
    {
        $projectDir = dirname(__DIR__, 2);
        $this->testFilePath = $projectDir . '/tests/data/test_house.csv';

        file_put_contents($this->testFilePath, '');

        $paramsMock = $this->createMock(ParameterBagInterface::class);
        $paramsMock->method('get')
            ->with('paths.house_csv')
            ->willReturn($this->testFilePath);
    }

    public function testGetAllHouses(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/house/create',[], [], [], json_encode( [
            'type' => 'house',
            'beds' => 2,
            'address' => 'address',
            'price' => 100
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
        $client->getResponse()->getContent();

        $client->request('GET', '/api/house/get');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        foreach ($data as $house) {
            $this->assertArrayHasKey('id', $house);
            $this->assertArrayHasKey('type', $house);
            $this->assertArrayHasKey('beds', $house);
            $this->assertArrayHasKey('address', $house);
            $this->assertArrayHasKey('price', $house);
            $this->assertArrayHasKey('free', $house);
        }
    }

    public function testGetAllFreeHouses(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/house/create',[], [], [], json_encode( [
            'type' => 'house free',
            'beds' => 2,
            'address' => 'address',
            'price' => 100,
            'free' => true
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
        $client->getResponse()->getContent();

        $client->request('POST', '/api/house/create',[], [], [], json_encode( [
            'type' => 'house buooking',
            'beds' => 2,
            'address' => 'address',
            'price' => 100,
            'free' => false
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
        $client->getResponse()->getContent();

        $client->request('GET', '/api/house/get_free');

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        foreach ($data as $house) {
            $this->assertArrayHasKey('id', $house);
            $this->assertArrayHasKey('type', $house);
            $this->assertArrayHasKey('beds', $house);
            $this->assertArrayHasKey('address', $house);
            $this->assertArrayHasKey('price', $house);
            $this->assertArrayHasKey('free', $house);
            $this->assertTrue($house['free']);
        }
    }
    public function testCreateHouse(): void
    {
        $client = static::createClient();

        $type = 'house';
        $beds = 2;
        $address = 'address1';
        $price = 100;

        $client->request('POST', '/api/house/create',[], [], [], json_encode( [
            'type' => $type,
            'beds' => $beds,
            'address' => $address,
            'price' => $price
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);

        $content = file_get_contents($this->testFilePath);
        $this->assertNotFalse($content);

        $this->assertStringContainsString($type, $content, 'Тип в файле отсутствует');
        $this->assertStringContainsString($beds, $content, 'Количество кроватей в файле отсутствует');
        $this->assertStringContainsString($address, $content, 'Адрес в файле отсутствует');
        $this->assertStringContainsString($price, $content, 'Цена в файле отсутствует');

    }

    public function testDeleteHouse(): void
    {
        $client = static::createClient();

        $type = 'house';
        $beds = 2;
        $address = 'address2';
        $price = 100;

        $client->request('POST', '/api/house/create',[], [], [], json_encode( [
            'type' => $type,
            'beds' => $beds,
            'address' => $address,
            'price' => $price
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);

        $id = $data['houseId'];

        $client->request('DELETE', '/api/house/delete', [], [], [], json_encode([
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

        $this->assertStringNotContainsString($type, $content, 'Тип остался в файле');
        $this->assertStringNotContainsString($beds, $content, 'Количество кроватей осталось в файле');
        $this->assertStringNotContainsString($address, $content, 'Адрес остался в файле');
        $this->assertStringNotContainsString($price, $content, 'Цена осталась в файле');
    }
}