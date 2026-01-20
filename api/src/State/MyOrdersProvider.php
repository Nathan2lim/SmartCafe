<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\OrderRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class MyOrdersProvider implements ProviderInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly OrderRepository $orderRepository,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();

        if (!$user) {
            throw new UnauthorizedHttpException('Bearer', 'Vous devez être connecté pour accéder à cette ressource');
        }

        return $this->orderRepository->findByCustomer($user);
    }
}
