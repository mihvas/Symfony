<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


class HouseControllerTest extends WebTestCase
{

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
        }
    }

    public function testCreateHouse(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/house/create',[], [], [], json_encode( [
            'type' => 'house',
            'beds' => 2,
            'address' => 'address1',
            'price' => 100
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }

    public function testDeleteHouse(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/house/create',[], [], [], json_encode( [
            'type' => 'house',
            'beds' => 2,
            'address' => 'address1',
            'price' => 100
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $client->request('DELETE', '/api/house/delete', [], [], [], json_encode([
            'id' => $data['houseId'],
        ]));

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
    }
}