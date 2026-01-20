<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Functional\ApiTestCase;

class AuthApiTest extends ApiTestCase
{
    public function testLoginSuccess(): void
    {
        $user = $this->createUser('login@test.fr', 'password123');

        $response = $this->client->request('POST', '/api/login', [
            'json' => [
                'email' => 'login@test.fr',
                'password' => 'password123',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('token', $data);
    }

    public function testLoginInvalidCredentials(): void
    {
        $this->createUser('login@test.fr', 'password123');

        $response = $this->client->request('POST', '/api/login', [
            'json' => [
                'email' => 'login@test.fr',
                'password' => 'wrongpassword',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetMeAuthenticated(): void
    {
        $user = $this->createUser('me@test.fr', 'password123');

        $response = $this->request('GET', '/api/auth/me', [], $user);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals('me@test.fr', $data['email']);
        $this->assertArrayHasKey('ordersUrl', $data);
        $this->assertArrayHasKey('loyaltyUrl', $data);
    }

    public function testGetMeUnauthenticated(): void
    {
        $response = $this->client->request('GET', '/api/auth/me');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRegisterUser(): void
    {
        $response = $this->client->request('POST', '/api/users', [
            'json' => [
                'email' => 'newuser@smartcafe.fr',
                'plainPassword' => 'securePassword123',
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertEquals('newuser@smartcafe.fr', $data['email']);
        $this->assertEquals('John', $data['firstName']);
    }

    public function testRegisterUserValidation(): void
    {
        $response = $this->client->request('POST', '/api/users', [
            'json' => [
                'email' => 'invalid-email',
                'firstName' => 'John',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }
}
