<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function testRegister(): void
    {
        $client = static::createClient();

        $phone = '79998887766';
        $password = 'TestPass123';

        $json = json_encode([
            'phoneNumber' => $phone,
            'password' => $password
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
        $this->assertArrayHasKey('refreshToken', $data);

        $em = $client->getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository(User::class);

        $user = $repo->findOneBy(['phone_number' => $phone]);
        $this->assertNotNull($user);
        $this->assertEquals($phone, $user->getPhoneNumber());
    }

    public function testLogin(): void
    {
        $client = static::createClient();

        $phone = '79998887777';
        $password = 'TestPass12334';

        $json = json_encode([
            'phoneNumber' => $phone,
            'password' => $password
        ]);

        $this->assertNotFalse($json);

        $client->request('POST', '/api/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $json);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);

        $client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $json);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);

        $data = json_decode($content, true);

        $this->assertArrayHasKey('token', $data);

        $this->assertArrayHasKey('refreshToken', $data);

        $token = $data['token'] ?? null;

        $this->assertNotNull($token);

        $refreshToken = $data['refreshToken'] ?? null;

        $this->assertNotNull($refreshToken);
    }

    public function testProfile(): void
    {
        $client = static::createClient();

        $phone = '79990001122';
        $password = 'SomePass123';

        $json = json_encode([
            'phoneNumber' => $phone,
            'password' => $password
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
        $token = $data['token'] ?? null;

        $this->assertNotNull($token);

        $client->request('GET', '/api/auth/profile', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);

        $data = json_decode($content, true);
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('phoneNumber', $data);
        $this->assertNotNull($data['phoneNumber'] ?? null);
        $this->assertEquals($phone, $data['phoneNumber'] ?? null);
    }

    public function testLogout(): void
    {
        $client = static::createClient();

        $phoneNumber = '70001112233';

        $json = json_encode([
            'phoneNumber' => $phoneNumber,
            'password' => 'LogoutTest123'
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

        $refreshToken = $data['refreshToken'] ?? null;

        $this->assertNotNull($refreshToken);

        $json = json_encode([
            'refreshToken' => $refreshToken
        ]);

        $this->assertNotFalse($json);

        $client->request('POST', '/api/auth/logout', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], $json);

        $this->assertResponseIsSuccessful();
        $this->assertResponseFormatSame('json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);

        $data = json_decode($content, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success'] ?? false);
    }
}
