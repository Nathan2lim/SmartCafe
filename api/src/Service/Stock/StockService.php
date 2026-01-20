<?php

namespace App\Service\Stock;

use App\Entity\Extra;
use App\Entity\Product;
use App\Exception\InsufficientStockException;
use App\Repository\ExtraRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

final class StockService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly ExtraRepository $extraRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    // Product stock methods

    public function checkProductAvailability(Product $product, int $quantity): bool
    {
        $stockQuantity = $product->getStockQuantity();

        // Si le stock est null, on considère qu'il n'y a pas de gestion de stock
        if ($stockQuantity === null) {
            return true;
        }

        return $stockQuantity >= $quantity;
    }

    public function deductProductStock(Product $product, int $quantity): void
    {
        $stockQuantity = $product->getStockQuantity();

        // Si le stock est null, pas de gestion de stock
        if ($stockQuantity === null) {
            return;
        }

        if ($stockQuantity < $quantity) {
            throw new InsufficientStockException(
                $product->getName(),
                $quantity,
                $stockQuantity
            );
        }

        $product->setStockQuantity($stockQuantity - $quantity);
        $product->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function restockProduct(Product $product, int $quantity): void
    {
        $currentStock = $product->getStockQuantity() ?? 0;
        $product->setStockQuantity($currentStock + $quantity);
        $product->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * @return Product[]
     */
    public function getLowStockProducts(): array
    {
        return $this->productRepository->findLowStock();
    }

    // Extra stock methods

    public function checkExtraAvailability(Extra $extra, int $quantity): bool
    {
        return $extra->getStockQuantity() >= $quantity;
    }

    public function deductExtraStock(Extra $extra, int $quantity): void
    {
        if ($extra->getStockQuantity() < $quantity) {
            throw new InsufficientStockException(
                $extra->getName(),
                $quantity,
                $extra->getStockQuantity()
            );
        }

        $extra->setStockQuantity($extra->getStockQuantity() - $quantity);
        $extra->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    public function restockExtra(Extra $extra, int $quantity): void
    {
        $extra->setStockQuantity($extra->getStockQuantity() + $quantity);
        $extra->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    /**
     * @return Extra[]
     */
    public function getLowStockExtras(): array
    {
        return $this->extraRepository->findLowStock();
    }

    // General methods

    public function isLowStock(Product|Extra $item): bool
    {
        if ($item instanceof Product) {
            $stockQuantity = $item->getStockQuantity();
            if ($stockQuantity === null) {
                return false;
            }
            return $stockQuantity <= $item->getLowStockThreshold();
        }

        return $item->getStockQuantity() <= $item->getLowStockThreshold();
    }

    public function restoreProductStock(Product $product, int $quantity): void
    {
        $this->restockProduct($product, $quantity);
    }

    public function restoreExtraStock(Extra $extra, int $quantity): void
    {
        $this->restockExtra($extra, $quantity);
    }
}
