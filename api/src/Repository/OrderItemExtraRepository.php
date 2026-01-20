<?php

namespace App\Repository;

use App\Entity\OrderItemExtra;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderItemExtra>
 */
class OrderItemExtraRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItemExtra::class);
    }

    public function save(OrderItemExtra $orderItemExtra, bool $flush = true): void
    {
        $this->getEntityManager()->persist($orderItemExtra);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OrderItemExtra $orderItemExtra, bool $flush = true): void
    {
        $this->getEntityManager()->remove($orderItemExtra);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
