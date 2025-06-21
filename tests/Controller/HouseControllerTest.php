<?php

namespace App\Tests\Controller;

use App\Entity\House;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HouseControllerTest extends WebTestCase
{
    public function testCreateHouse(): void
    {
        $client = static::createClient();

        $type = 'test house1';
        $beds = 2;
        $address = 'test address';
        $price = 500;

        $client->request('POST', '/api/house/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'type' => $type,
            'beds' => $beds,
            'address' => $address,
            'price' => $price,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('houseId', $data);

        $id = $data['houseId'];

        $em = $client->getContainer()->get('doctrine')->getManager();

        $houseFromDb = $em->getRepository(House::class)->findById($id);

        $this->assertNotNull($houseFromDb);

        $this->assertEquals($type, $houseFromDb->getType(),'Тип отсутствует');
        $this->assertEquals($beds, $houseFromDb->getBeds(),'Количество кроватей отсутствует');
        $this->assertEquals($address, $houseFromDb->getAddress(),'Адрес отсутствует');
        $this->assertEquals($price, $houseFromDb->getPrice(),'Цена отсутствует');

    }

    public function testGetAllHouses(): void
    {
        $client = static::createClient();

        $type = 'test house2';
        $beds = 2;
        $address = 'test address';
        $price = 100;

        $client->request('POST', '/api/house/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'type' => $type,
            'beds' => $beds,
            'address' => $address,
            'price' => $price,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $id = $data['houseId'];

        $client->request('GET', '/api/house/get');
        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
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

        $type = 'test house3';
        $beds = 3;
        $address = 'test address';
        $price = 150;

        $type1 = 'test house booked';
        $beds1 = 2;
        $address1 = 'test address';
        $price1 = 200;

        $client->request('POST', '/api/house/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'type' => $type,
            'beds' => $beds,
            'address' => $address,
            'price' => $price,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $id = $data['houseId'];

        $client->request('POST', '/api/house/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'type' => $type1,
            'beds' => $beds1,
            'address' => $address1,
            'price' => $price1,
            'free' => false,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $id1 = $data['houseId'];

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

    public function testDeleteHouse(): void
    {
        $client = static::createClient();

        $type = 'test delete';
        $beds = 2;
        $address = 'test address';
        $price = 300;

        $client->request('POST', '/api/house/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'type' => $type,
            'beds' => $beds,
            'address' => $address,
            'price' => $price,
        ]));
        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $id = $data['houseId'];

        $client->request('DELETE', '/api/house/delete', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'id' => $id,
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);

        $em = $client->getContainer()->get('doctrine')->getManager();


        $houseFromDb = $em->getRepository(House::class)->findById($id);

        $this->assertNull($houseFromDb);
    }
}
