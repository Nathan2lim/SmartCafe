<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use App\Service\Auth\RefreshTokenService;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RefreshTokenServiceTest extends TestCase
{
    private RefreshTokenService $refreshTokenService;
    private MockObject&EntityManagerInterface $entityManager;
    private MockObject&RefreshTokenRepository $refreshTokenRepository;
    private MockObject&JWTTokenManagerInterface $jwtManager;
    private MockObject&RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->refreshTokenRepository = $this->createMock(RefreshTokenRepository::class);
        $this->jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->refreshTokenService = new RefreshTokenService(
            $this->entityManager,
            $this->refreshTokenRepository,
            $this->jwtManager,
            $this->requestStack,
        );
    }

    public function testCreateRefreshToken(): void
    {
        $user = $this->createUser();
        $request = $this->createMock(Request::class);
        $request->method('getClientIp')->willReturn('192.168.1.1');
        $request->headers = $this->createMock(\Symfony\Component\HttpFoundation\HeaderBag::class);
        $request->headers->method('get')->with('User-Agent')->willReturn('Mozilla/5.0');

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $refreshToken = $this->refreshTokenService->createRefreshToken($user);

        $this->assertInstanceOf(RefreshToken::class, $refreshToken);
        $this->assertEquals($user, $refreshToken->getUser());
        $this->assertNotEmpty($refreshToken->getToken());
        $this->assertFalse($refreshToken->isRevoked());
    }

    public function testCreateRefreshTokenWithoutRequest(): void
    {
        $user = $this->createUser();

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $refreshToken = $this->refreshTokenService->createRefreshToken($user);

        $this->assertInstanceOf(RefreshToken::class, $refreshToken);
        $this->assertNull($refreshToken->getIpAddress());
        $this->assertNull($refreshToken->getUserAgent());
    }

    public function testRefreshSuccess(): void
    {
        $user = $this->createUser();
        $oldRefreshToken = $this->createRefreshToken($user, 'old_token');
        $newRefreshToken = $this->createRefreshToken($user, 'new_token');

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findValidByToken')
            ->with('old_token')
            ->willReturn($oldRefreshToken);

        $this->jwtManager
            ->expects($this->once())
            ->method('create')
            ->with($user)
            ->willReturn('new_jwt_token');

        $this->requestStack
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->entityManager
            ->expects($this->once())
            ->method('persist');

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('flush');

        $result = $this->refreshTokenService->refresh('old_token');

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('refresh_token_expires_at', $result);
        $this->assertEquals('new_jwt_token', $result['token']);
        $this->assertTrue($oldRefreshToken->isRevoked());
    }

    public function testRefreshWithInvalidToken(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findValidByToken')
            ->with('invalid_token')
            ->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid or expired refresh token');

        $this->refreshTokenService->refresh('invalid_token');
    }

    public function testRevokeTokenSuccess(): void
    {
        $user = $this->createUser();
        $refreshToken = $this->createRefreshToken($user, 'token_to_revoke');

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findValidByToken')
            ->with('token_to_revoke')
            ->willReturn($refreshToken);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $result = $this->refreshTokenService->revokeToken('token_to_revoke');

        $this->assertTrue($result);
        $this->assertTrue($refreshToken->isRevoked());
    }

    public function testRevokeTokenNotFound(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findValidByToken')
            ->with('nonexistent_token')
            ->willReturn(null);

        $result = $this->refreshTokenService->revokeToken('nonexistent_token');

        $this->assertFalse($result);
    }

    public function testRevokeAllForUser(): void
    {
        $user = $this->createUser();

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('revokeAllForUser')
            ->with($user)
            ->willReturn(3);

        $result = $this->refreshTokenService->revokeAllForUser($user);

        $this->assertEquals(3, $result);
    }

    public function testCleanupExpired(): void
    {
        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('deleteExpired')
            ->willReturn(5);

        $result = $this->refreshTokenService->cleanupExpired();

        $this->assertEquals(5, $result);
    }

    public function testGetActiveSessions(): void
    {
        $user = $this->createUser();
        $tokens = [
            $this->createRefreshToken($user, 'token1'),
            $this->createRefreshToken($user, 'token2'),
        ];

        $this->refreshTokenRepository
            ->expects($this->once())
            ->method('findActiveByUser')
            ->with($user)
            ->willReturn($tokens);

        $result = $this->refreshTokenService->getActiveSessions($user);

        $this->assertCount(2, $result);
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

    private function createRefreshToken(User $user, string $token): RefreshToken
    {
        $refreshToken = RefreshToken::create($user, 2592000);
        $reflection = new ReflectionClass($refreshToken);
        $tokenProperty = $reflection->getProperty('token');
        $tokenProperty->setValue($refreshToken, $token);

        return $refreshToken;
    }
}
