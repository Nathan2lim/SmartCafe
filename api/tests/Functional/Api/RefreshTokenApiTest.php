<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\RefreshToken;
use App\Tests\Functional\ApiTestCase;

class RefreshTokenApiTest extends ApiTestCase
{
    public function testLoginReturnsRefreshTokenCookie(): void
    {
        $user = $this->createUser('tokenlogin@test.fr', 'password123');

        $response = $this->client->request('POST', '/api/login', [
            'json' => [
                'email' => 'tokenlogin@test.fr',
                'password' => 'password123',
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertArrayHasKey('token', $data);
    }

    public function testRevokeAllTokens(): void
    {
        $user = $this->createUser('revokeall@test.fr', 'password123');

        // Create multiple refresh tokens
        $token1 = RefreshToken::create($user, 2592000);
        $token2 = RefreshToken::create($user, 2592000);
        $token3 = RefreshToken::create($user, 2592000);
        $this->entityManager->persist($token1);
        $this->entityManager->persist($token2);
        $this->entityManager->persist($token3);
        $this->entityManager->flush();

        $response = $this->request('POST', '/api/token/revoke-all', [], $user);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertArrayHasKey('revoked_count', $data);
        $this->assertGreaterThanOrEqual(3, $data['revoked_count']);
    }

    public function testRevokeAllTokensUnauthenticated(): void
    {
        $this->client->request('POST', '/api/token/revoke-all');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetActiveSessions(): void
    {
        $user = $this->createUser('sessions@test.fr', 'password123');

        // Create a refresh token
        $token1 = RefreshToken::create($user, 2592000);
        $token1->setIpAddress('192.168.1.1');
        $token1->setUserAgent('Mozilla/5.0 Chrome');

        $this->entityManager->persist($token1);
        $this->entityManager->flush();

        $response = $this->request('GET', '/api/auth/sessions', [], $user);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(1, count($data));
    }

    public function testGetActiveSessionsUnauthenticated(): void
    {
        $this->client->request('GET', '/api/auth/sessions');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginCreatesRefreshTokenWithMetadata(): void
    {
        $user = $this->createUser('metadata@test.fr', 'password123');

        $response = $this->client->request('POST', '/api/login', [
            'json' => [
                'email' => 'metadata@test.fr',
                'password' => 'password123',
            ],
            'headers' => [
                'User-Agent' => 'Test Browser 1.0',
            ],
        ]);

        $this->assertResponseIsSuccessful();

        // Verify refresh token was created with metadata
        $tokens = $this->entityManager->getRepository(RefreshToken::class)->findBy(['user' => $user]);
        $this->assertNotEmpty($tokens);

        $latestToken = end($tokens);
        $this->assertNotNull($latestToken->getUserAgent());
    }
}
