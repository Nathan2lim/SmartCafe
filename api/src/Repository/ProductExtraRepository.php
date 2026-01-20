<?php

namespace App\Repository;

use App\Entity\Extra;
use App\Entity\Product;
use App\Entity\ProductExtra;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductExtra>
 */
class ProductExtraRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductExtra::class);
    }

    public function save(ProductExtra $productExtra, bool $flush = true): void
    {
        $this->getEntityManager()->persist($productExtra);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProductExtra $productExtra, bool $flush = true): void
    {
        $this->getEntityManager()->remove($productExtra);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByProduct(Product $product): array
    {
        return $this->createQueryBuilder('pe')
            ->andWhere('pe.product = :product')
            ->setParameter('product', $product)
            ->join('pe.extra', 'e')
            ->andWhere('e.available = :available')
            ->setParameter('available', true)
            ->getQuery()
            ->getResult();
    }

    public function findByProductAndExtra(Product $product, Extra $extra): ?ProductExtra
    {
        return $this->createQueryBuilder('pe')
            ->andWhere('pe.product = :product')
            ->andWhere('pe.extra = :extra')
            ->setParameter('product', $product)
            ->setParameter('extra', $extra)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
