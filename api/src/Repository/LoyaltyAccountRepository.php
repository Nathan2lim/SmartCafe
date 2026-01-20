<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LoyaltyAccount;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoyaltyAccount>
 */
class LoyaltyAccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoyaltyAccount::class);
    }

    public function save(LoyaltyAccount $account, bool $flush = true): void
    {
        $this->getEntityManager()->persist($account);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUser(User $user): ?LoyaltyAccount
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function findOrCreateByUser(User $user): LoyaltyAccount
    {
        $account = $this->findByUser($user);

        if (!$account) {
            $account = new LoyaltyAccount();
            $account->setUser($user);
            $this->save($account);
        }

        return $account;
    }

    /**
     * @return LoyaltyAccount[]
     */
    public function findByTier(string $tier): array
    {
        return $this->findBy(['tier' => $tier], ['points' => 'DESC']);
    }

    /**
     * @return LoyaltyAccount[]
     */
    public function findTopAccounts(int $limit = 10): array
    {
        return $this->createQueryBuilder('la')
            ->orderBy('la.totalPointsEarned', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
