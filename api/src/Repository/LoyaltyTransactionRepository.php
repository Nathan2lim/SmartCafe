<?php

namespace App\Repository;

use App\Entity\LoyaltyAccount;
use App\Entity\LoyaltyTransaction;
use App\Enum\LoyaltyTransactionType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoyaltyTransaction>
 */
class LoyaltyTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoyaltyTransaction::class);
    }

    public function save(LoyaltyTransaction $transaction, bool $flush = true): void
    {
        $this->getEntityManager()->persist($transaction);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return LoyaltyTransaction[]
     */
    public function findByAccount(LoyaltyAccount $account, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('lt')
            ->andWhere('lt.account = :account')
            ->setParameter('account', $account)
            ->orderBy('lt.createdAt', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return LoyaltyTransaction[]
     */
    public function findByAccountAndType(LoyaltyAccount $account, LoyaltyTransactionType $type): array
    {
        return $this->createQueryBuilder('lt')
            ->andWhere('lt.account = :account')
            ->andWhere('lt.type = :type')
            ->setParameter('account', $account)
            ->setParameter('type', $type)
            ->orderBy('lt.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function sumPointsByAccountAndType(LoyaltyAccount $account, LoyaltyTransactionType $type): int
    {
        $result = $this->createQueryBuilder('lt')
            ->select('SUM(lt.points)')
            ->andWhere('lt.account = :account')
            ->andWhere('lt.type = :type')
            ->setParameter('account', $account)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }
}
