<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function save(Product $product): void
    {
        $this->getEntityManager()->persist($product);
        $this->getEntityManager()->flush();
    }

    public function remove(Product $product): void
    {
        $this->getEntityManager()->remove($product);
        $this->getEntityManager()->flush();
    }

    /**
     * @return Product[]
     */
    public function findAvailable(): array
    {
        return $this->findBy(['available' => true]);
    }

    /**
     * @return Product[]
     */
    public function findByCategory(string $category): array
    {
        return $this->findBy(['category' => $category, 'available' => true]);
    }

    /**
     * @return Product[]
     */
    public function findLowStock(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.stockQuantity IS NOT NULL')
            ->andWhere('p.stockQuantity <= p.lowStockThreshold')
            ->orderBy('p.stockQuantity', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
