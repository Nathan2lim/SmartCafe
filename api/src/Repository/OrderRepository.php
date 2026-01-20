<?php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function save(Order $order): void
    {
        $this->getEntityManager()->persist($order);
        $this->getEntityManager()->flush();
    }

    public function remove(Order $order): void
    {
        $this->getEntityManager()->remove($order);
        $this->getEntityManager()->flush();
    }

    /**
     * @return Order[]
     */
    public function findByCustomer(User $customer): array
    {
        return $this->findBy(['customer' => $customer], ['createdAt' => 'DESC']);
    }

    /**
     * @return Order[]
     */
    public function findByStatus(OrderStatus $status): array
    {
        return $this->findBy(['status' => $status], ['createdAt' => 'ASC']);
    }

    /**
     * @return Order[]
     */
    public function findActiveOrders(): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.status NOT IN (:finalStatuses)')
            ->setParameter('finalStatuses', [OrderStatus::DELIVERED, OrderStatus::CANCELLED])
            ->orderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Order[]
     */
    public function findTodayOrders(): array
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = new \DateTimeImmutable('tomorrow');

        return $this->createQueryBuilder('o')
            ->where('o.createdAt >= :today')
            ->andWhere('o.createdAt < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
