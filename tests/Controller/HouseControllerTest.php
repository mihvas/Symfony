<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\House;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HouseControllerTest extends WebTestCase
{
    private function register(KernelBrowser $client): ?string
    {
        $phone = '79998887766';
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

    public function testCreateHouse(): void
    {
        $client = static::createClient();
        $token = $this->register($client);

        $type = 'test house1';
        $beds = 2;
        $address = 'test address';
        $price = 500;

        $json = json_encode([
            'type' => $type,
            'beds' => $beds,
            'address' => $address,
            'price' => $price,
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

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success'] ?? false);
        $this->assertArrayHasKey('houseId', $data);

        $id = $data['houseId'] ?? null;

        $this->assertNotNull($id);

        $em = $client->getContainer()->get('doctrine')->getManager();

        $houseFromDb = $em->getRepository(House::class)->findById($id);

        $this->assertNotNull($houseFromDb);

        $this->assertEquals($type, $houseFromDb->getType(), 'Тип отсутствует');
        $this->assertEquals($beds, $houseFromDb->getBeds(), 'Количество кроватей отсутствует');
        $this->assertEquals($address, $houseFromDb->getAddress(), 'Адрес отсутствует');
        $this->assertEquals($price, $houseFromDb->getPrice(), 'Цена отсутствует');
    }

    public function testGetAllHouses(): void
    {
        $client = static::createClient();
        $token = $this->register($client);

        $type = 'test house2';
        $beds = 2;
        $address = 'test address';
        $price = 100;

        $json = json_encode([
            'type' => $type,
            'beds' => $beds,
            'address' => $address,
            'price' => $price,
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

        $id = $data['houseId'] ?? null;

        $this->assertNotNull($id);

        $client->request('GET', '/api/house/get', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
            ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertGreaterThan(0, count($data));

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
        $token = $this->register($client);

        $type = 'test house3';
        $beds = 3;
        $address = 'test address';
        $price = 150;

        $type1 = 'test house booked';
        $beds1 = 2;
        $address1 = 'test address';
        $price1 = 200;

        $json = json_encode([
            'type' => $type,
            'beds' => $beds,
            'address' => $address,
            'price' => $price,
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
        $id = $data['houseId'] ?? null;

        $this->assertNotNull($id);

        $json = json_encode([
            'type' => $type1,
            'beds' => $beds1,
            'address' => $address1,
            'price' => $price1,
            'free' => false,
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
        $id1 = $data['houseId'] ?? null;

        $this->assertNotNull($id1);

        $client->request('GET', '/api/house/get_free', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        foreach ($data as $house) {
            $this->assertArrayHasKey('id', $house);
            $this->assertArrayHasKey('type', $house);
            $this->assertArrayHasKey('beds', $house);
            $this->assertArrayHasKey('address', $house);
            $this->assertArrayHasKey('price', $house);
            $this->assertArrayHasKey('free', $house);
            $this->assertTrue($house['free'] ?? false);
        }
    }

    public function testDeleteHouse(): void
    {
        $client = static::createClient();
        $token = $this->register($client);

        $type = 'test delete';
        $beds = 2;
        $address = 'test address';
        $price = 300;

        $json = json_encode([
            'type' => $type,
            'beds' => $beds,
            'address' => $address,
            'price' => $price,
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

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success'] ?? false);
        $this->assertArrayHasKey('houseId', $data);

        $id = $data['houseId'] ?? null;

        $this->assertNotNull($id);

        $json = json_encode([
            'id' => $id,
        ]);

        $this->assertNotFalse($json);

        $client->request('DELETE', '/api/house/delete', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_Authorization' => 'Bearer ' . $token,
        ], $json);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
        $content = $client->getResponse()->getContent();

        $this->assertNotFalse($content);
        $data = json_decode($content, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success'] ?? false);

        $em = $client->getContainer()->get('doctrine')->getManager();

        $houseFromDb = $em->getRepository(House::class)->findById($id);

        $this->assertNull($houseFromDb);
    }
}
