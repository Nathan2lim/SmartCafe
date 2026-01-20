<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\Auth\RefreshTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JwtCreatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RefreshTokenService $refreshTokenService,
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

        $data = $event->getData();
        $data['refresh_token'] = $refreshToken->getToken();
        $data['refresh_token_expires_at'] = $refreshToken->getExpiresAt()->format('c');

        $event->setData($data);
    }
}
