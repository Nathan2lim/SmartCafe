<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\Auth\RefreshTokenService;
use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
class AuthController extends AbstractController
{
    private const REFRESH_TOKEN_COOKIE = 'refresh_token';
    private const REFRESH_TOKEN_PATH = '/api/token';

    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
        private readonly string $appEnv = 'prod',
    ) {
    }

    #[Route('/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->cookies->get(self::REFRESH_TOKEN_COOKIE);

        if (!$refreshToken) {
            return $this->json([
                'error' => 'Missing refresh_token cookie',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $tokens = $this->refreshTokenService->refresh($refreshToken);

            $response = $this->json([
                'token' => $tokens['token'],
                'refresh_token_expires_at' => $tokens['refresh_token_expires_at'],
            ]);

            // Refresh token cookie
            $expiresAt = new DateTimeImmutable($tokens['refresh_token_expires_at']);
            $refreshCookie = new Cookie(
                self::REFRESH_TOKEN_COOKIE,
                $tokens['refresh_token'],
                $expiresAt,
                self::REFRESH_TOKEN_PATH,
                null,
                'prod' === $this->appEnv,
                true,
                false,
                Cookie::SAMESITE_STRICT,
            );
            $response->headers->setCookie($refreshCookie);

            return $response;
        } catch (InvalidArgumentException $e) {
            $response = $this->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_UNAUTHORIZED);

            $response->headers->clearCookie(self::REFRESH_TOKEN_COOKIE, self::REFRESH_TOKEN_PATH);

            return $response;
        }
    }

    #[Route('/token/revoke', name: 'api_token_revoke', methods: ['POST'])]
    public function revoke(Request $request): JsonResponse
    {
        $refreshToken = $request->cookies->get(self::REFRESH_TOKEN_COOKIE);

        if (!$refreshToken) {
            return $this->json([
                'error' => 'Missing refresh_token cookie',
            ], Response::HTTP_BAD_REQUEST);
        }

        $revoked = $this->refreshTokenService->revokeToken($refreshToken);

        $response = $this->json([
            'message' => $revoked ? 'Token revoked successfully' : 'Token already revoked or invalid',
        ]);

        $response->headers->clearCookie(self::REFRESH_TOKEN_COOKIE, self::REFRESH_TOKEN_PATH);

        return $response;
    }

    #[Route('/token/revoke-all', name: 'api_token_revoke_all', methods: ['POST'])]
    public function revokeAll(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $count = $this->refreshTokenService->revokeAllForUser($user);

        $response = $this->json([
            'message' => "Revoked {$count} active sessions",
            'revoked_count' => $count,
        ]);

        $response->headers->clearCookie(self::REFRESH_TOKEN_COOKIE, self::REFRESH_TOKEN_PATH);

        return $response;
    }

    #[Route('/auth/sessions', name: 'api_auth_sessions', methods: ['GET'])]
    public function sessions(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'error' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $sessions = $this->refreshTokenService->getActiveSessions($user);

        return $this->json([
            'sessions' => array_map(fn ($session) => [
                'created_at' => $session->getCreatedAt()->format('c'),
                'expires_at' => $session->getExpiresAt()->format('c'),
                'ip_address' => $session->getIpAddress(),
                'user_agent' => $session->getUserAgent(),
            ], $sessions),
        ]);
    }
}
