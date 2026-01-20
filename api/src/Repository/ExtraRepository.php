<?php

namespace App\Repository;

use App\Entity\Extra;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Extra>
 */
class ExtraRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Extra::class);
    }

    public function save(Extra $extra, bool $flush = true): void
    {
        $this->getEntityManager()->persist($extra);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Extra $extra, bool $flush = true): void
    {
        $this->getEntityManager()->remove($extra);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Extra[]
     */
    public function findAvailable(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.available = :available')
            ->setParameter('available', true)
            ->orderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Extra[]
     */
    public function findLowStock(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.stockQuantity <= e.lowStockThreshold')
            ->orderBy('e.stockQuantity', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
