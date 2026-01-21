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
    private const COOKIE_NAME = 'refresh_token';
    private const COOKIE_PATH = '/api/token';

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

        $refreshToken = $this->refreshTokenService->createRefreshToken($user);

        $cookie = new Cookie(
            self::COOKIE_NAME,
            $refreshToken->getToken(),
            $refreshToken->getExpiresAt(),
            self::COOKIE_PATH,
            null,
            $this->appEnv === 'prod',
            true,
            false,
            Cookie::SAMESITE_STRICT
        );

        $response = $event->getResponse();
        $response->headers->setCookie($cookie);

        $data = $event->getData();
        $data['refresh_token_expires_at'] = $refreshToken->getExpiresAt()->format('c');
        $event->setData($data);
    }
}
