<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\RefreshToken;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class RefreshTokenTest extends TestCase
{
    public function testCreateRefreshToken(): void
    {
        $user = $this->createUser();
        $ttl = 2592000; // 30 days

        $refreshToken = RefreshToken::create($user, $ttl);

        $this->assertInstanceOf(RefreshToken::class, $refreshToken);
        $this->assertSame($user, $refreshToken->getUser());
        $this->assertNotEmpty($refreshToken->getToken());
        // Token length can vary, just ensure it's not empty
        $this->assertGreaterThan(0, strlen($refreshToken->getToken()));
        $this->assertFalse($refreshToken->isRevoked());
        $this->assertNotNull($refreshToken->getCreatedAt());
        $this->assertNotNull($refreshToken->getExpiresAt());
    }

    public function testRefreshTokenExpiration(): void
    {
        $user = $this->createUser();
        $ttl = 3600; // 1 hour

        $refreshToken = RefreshToken::create($user, $ttl);

        $expectedExpiration = (new \DateTimeImmutable())->modify('+3600 seconds');
        $actualExpiration = $refreshToken->getExpiresAt();

        // Allow 2 seconds difference for test execution time
        $this->assertEqualsWithDelta(
            $expectedExpiration->getTimestamp(),
            $actualExpiration->getTimestamp(),
            2,
        );
    }

    public function testIsValidWhenNotRevoked(): void
    {
        $user = $this->createUser();
        $refreshToken = RefreshToken::create($user, 3600);

        $this->assertTrue($refreshToken->isValid());
    }

    public function testIsValidWhenRevoked(): void
    {
        $user = $this->createUser();
        $refreshToken = RefreshToken::create($user, 3600);

        $refreshToken->revoke();

        $this->assertFalse($refreshToken->isValid());
    }

    public function testIsValidWhenExpired(): void
    {
        $user = $this->createUser();
        $refreshToken = RefreshToken::create($user, -1); // Already expired

        $this->assertFalse($refreshToken->isValid());
    }

    public function testRevoke(): void
    {
        $user = $this->createUser();
        $refreshToken = RefreshToken::create($user, 3600);

        $this->assertFalse($refreshToken->isRevoked());

        $refreshToken->revoke();

        $this->assertTrue($refreshToken->isRevoked());
    }

    public function testSetIpAddress(): void
    {
        $user = $this->createUser();
        $refreshToken = RefreshToken::create($user, 3600);

        $refreshToken->setIpAddress('192.168.1.1');

        $this->assertEquals('192.168.1.1', $refreshToken->getIpAddress());
    }

    public function testSetUserAgent(): void
    {
        $user = $this->createUser();
        $refreshToken = RefreshToken::create($user, 3600);

        $refreshToken->setUserAgent('Mozilla/5.0');

        $this->assertEquals('Mozilla/5.0', $refreshToken->getUserAgent());
    }

    public function testTokenIsUnique(): void
    {
        $user = $this->createUser();

        $token1 = RefreshToken::create($user, 3600);
        $token2 = RefreshToken::create($user, 3600);

        $this->assertNotEquals($token1->getToken(), $token2->getToken());
    }

    // Helper methods
    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('test@smartcafe.fr');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword('hashed_password');

        return $user;
    }
}
