<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\ProductRepository;

final class LowStockProductsProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return $this->productRepository->findLowStock();
    }
}
