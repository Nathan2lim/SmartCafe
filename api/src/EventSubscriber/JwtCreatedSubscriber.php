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

        // Refresh token cookie
        $refreshToken = $this->refreshTokenService->createRefreshToken($user);
        $refreshCookie = new Cookie(
            self::REFRESH_TOKEN_COOKIE,
            $refreshToken->getToken(),
            $refreshToken->getExpiresAt(),
            self::REFRESH_TOKEN_PATH,
            null,
            'prod' === $this->appEnv,
            true,
            false,
            Cookie::SAMESITE_STRICT,
        );
        $response->headers->setCookie($refreshCookie);

        // JWT stays in body, add refresh token expiration
        $data['refresh_token_expires_at'] = $refreshToken->getExpiresAt()->format('c');
        $event->setData($data);
    }
}
