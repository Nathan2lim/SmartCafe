<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ExtraRepository;

final class LowStockExtrasProvider implements ProviderInterface
{
    public function __construct(
        private readonly ExtraRepository $extraRepository,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->extraRepository->findLowStock();
    }
}
