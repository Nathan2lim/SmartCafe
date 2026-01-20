<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RefreshTokenService
{
    private const REFRESH_TOKEN_TTL = 2592000; // 30 days in seconds

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Create a new refresh token for a user.
     */
    public function createRefreshToken(User $user): RefreshToken
    {
        $refreshToken = RefreshToken::create($user, self::REFRESH_TOKEN_TTL);

        $request = $this->requestStack->getCurrentRequest();
        if ($request) {
            $refreshToken->setIpAddress($request->getClientIp());
            $refreshToken->setUserAgent($request->headers->get('User-Agent'));
        }

        $this->entityManager->persist($refreshToken);
        $this->entityManager->flush();

        return $refreshToken;
    }

    /**
     * Refresh the JWT token using a valid refresh token.
     *
     * @return array{token: string, refresh_token: string}
     *
     * @throws \InvalidArgumentException if refresh token is invalid
     */
    public function refresh(string $refreshTokenString): array
    {
        $refreshToken = $this->refreshTokenRepository->findValidByToken($refreshTokenString);

        if (!$refreshToken) {
            throw new \InvalidArgumentException('Invalid or expired refresh token');
        }

        $user = $refreshToken->getUser();

        // Revoke the old refresh token (rotation)
        $refreshToken->revoke();

        // Create new tokens
        $newJwtToken = $this->jwtManager->create($user);
        $newRefreshToken = $this->createRefreshToken($user);

        $this->entityManager->flush();

        return [
            'token' => $newJwtToken,
            'refresh_token' => $newRefreshToken->getToken(),
        ];
    }

    /**
     * Revoke a specific refresh token.
     */
    public function revokeToken(string $refreshTokenString): bool
    {
        $refreshToken = $this->refreshTokenRepository->findValidByToken($refreshTokenString);

        if (!$refreshToken) {
            return false;
        }

        $refreshToken->revoke();
        $this->entityManager->flush();

        return true;
    }

    /**
     * Revoke all refresh tokens for a user (logout from all devices).
     */
    public function revokeAllForUser(User $user): int
    {
        return $this->refreshTokenRepository->revokeAllForUser($user);
    }

    /**
     * Clean up expired refresh tokens.
     */
    public function cleanupExpired(): int
    {
        return $this->refreshTokenRepository->deleteExpired();
    }

    /**
     * Get all active sessions for a user.
     *
     * @return RefreshToken[]
     */
    public function getActiveSessions(User $user): array
    {
        return $this->refreshTokenRepository->findActiveByUser($user);
    }
}
