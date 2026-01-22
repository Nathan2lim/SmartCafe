<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\LoyaltyTransaction;
use App\Entity\User;
use App\Service\Loyalty\LoyaltyService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class UpgradeTierProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly LoyaltyService $loyaltyService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): LoyaltyTransaction
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Vous devez être connecté pour upgrader votre carte');
        }

        return $this->loyaltyService->upgradeTier($user);
    }
}
