<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Service\Loyalty\LoyaltyService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class MyLoyaltyProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly LoyaltyService $loyaltyService,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new UnauthorizedHttpException('Bearer', 'Vous devez être connecté pour accéder à cette ressource');
        }

        return $this->loyaltyService->getOrCreateAccount($user);
    }
}
