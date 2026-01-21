<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Auth\RefreshTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;

class JwtCreatedSubscriber implements EventSubscriberInterface
{
    private const REFRESH_TOKEN_COOKIE = 'refresh_token';
    private const REFRESH_TOKEN_PATH = '/api/token';
    private const JWT_COOKIE = 'jwt_token';
    private const JWT_PATH = '/api';
    private const JWT_TTL = 3600;

    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
        private readonly string $appEnv = 'prod',
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $response = $event->getResponse();
        $data = $event->getData();
        $isSecure = $this->appEnv === 'prod';

        // JWT cookie
        $jwtToken = $data['token'] ?? null;
        if ($jwtToken) {
            $jwtCookie = new Cookie(
                self::JWT_COOKIE,
                $jwtToken,
                time() + self::JWT_TTL,
                self::JWT_PATH,
                null,
                $isSecure,
                true,
                false,
                Cookie::SAMESITE_STRICT
            );
            $response->headers->setCookie($jwtCookie);
        }

        // Refresh token cookie
        $refreshToken = $this->refreshTokenService->createRefreshToken($user);
        $refreshCookie = new Cookie(
            self::REFRESH_TOKEN_COOKIE,
            $refreshToken->getToken(),
            $refreshToken->getExpiresAt(),
            self::REFRESH_TOKEN_PATH,
            null,
            $isSecure,
            true,
            false,
            Cookie::SAMESITE_STRICT
        );
        $response->headers->setCookie($refreshCookie);

        // Remove tokens from response body, keep only expiration info
        unset($data['token']);
        $data['refresh_token_expires_at'] = $refreshToken->getExpiresAt()->format('c');
        $event->setData($data);
    }
}
