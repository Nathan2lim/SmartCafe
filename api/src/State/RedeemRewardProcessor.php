<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\LoyaltyTransaction;
use App\Service\Loyalty\LoyaltyService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class RedeemRewardProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly LoyaltyService $loyaltyService,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): LoyaltyTransaction
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new UnauthorizedHttpException('Bearer', 'Vous devez être connecté pour échanger une récompense');
        }

        $rewardId = $uriVariables['id'];
        $reward = $this->loyaltyService->getRewardById($rewardId);

        return $this->loyaltyService->redeemReward($user, $reward);
    }
}
