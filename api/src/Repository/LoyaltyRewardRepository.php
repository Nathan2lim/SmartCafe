<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LoyaltyReward;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoyaltyReward>
 */
class LoyaltyRewardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoyaltyReward::class);
    }

    public function save(LoyaltyReward $reward, bool $flush = true): void
    {
        $this->getEntityManager()->persist($reward);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LoyaltyReward $reward, bool $flush = true): void
    {
        $this->getEntityManager()->remove($reward);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return LoyaltyReward[]
     */
    public function findAvailable(): array
    {
        return $this->createQueryBuilder('lr')
            ->andWhere('lr.active = :active')
            ->andWhere('lr.stockQuantity IS NULL OR lr.stockQuantity > 0')
            ->setParameter('active', true)
            ->orderBy('lr.pointsCost', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return LoyaltyReward[]
     */
    public function findAvailableForTier(string $tier): array
    {
        $tierOrder = ['bronze' => 0, 'silver' => 1, 'gold' => 2, 'platinum' => 3];
        $userTierLevel = $tierOrder[$tier] ?? 0;

        $rewards = $this->findAvailable();

        return array_filter($rewards, function (LoyaltyReward $reward) use ($tierOrder, $userTierLevel) {
            $requiredTier = $reward->getRequiredTier();
            if (null === $requiredTier) {
                return true;
            }
            $requiredLevel = $tierOrder[$requiredTier] ?? 0;

            return $userTierLevel >= $requiredLevel;
        });
    }

    /**
     * @return LoyaltyReward[]
     */
    public function findAffordable(int $points, string $tier): array
    {
        $availableForTier = $this->findAvailableForTier($tier);

        return array_filter($availableForTier, function (LoyaltyReward $reward) use ($points) {
            return $reward->getPointsCost() <= $points;
        });
    }
}
