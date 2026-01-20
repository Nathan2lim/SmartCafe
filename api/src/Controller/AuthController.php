<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\Auth\RefreshTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
    ) {
    }

    #[Route('/token/refresh', name: 'api_token_refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refresh_token'] ?? null;

        if (!$refreshToken) {
            return $this->json([
                'error' => 'Missing refresh_token',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $tokens = $this->refreshTokenService->refresh($refreshToken);

            return $this->json($tokens);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/token/revoke', name: 'api_token_revoke', methods: ['POST'])]
    public function revoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $refreshToken = $data['refresh_token'] ?? null;

        if (!$refreshToken) {
            return $this->json([
                'error' => 'Missing refresh_token',
            ], Response::HTTP_BAD_REQUEST);
        }

        $revoked = $this->refreshTokenService->revokeToken($refreshToken);

        if (!$revoked) {
            return $this->json([
                'error' => 'Invalid refresh token',
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'message' => 'Token revoked successfully',
        ]);
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

        return $this->json([
            'message' => "Revoked {$count} active sessions",
            'revoked_count' => $count,
        ]);
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
