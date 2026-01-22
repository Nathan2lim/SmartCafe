<?php

declare(strict_types=1);

namespace App\Service\Loyalty;

use App\Entity\LoyaltyAccount;
use App\Entity\LoyaltyReward;
use App\Entity\LoyaltyTransaction;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\LoyaltyTransactionType;
use App\Exception\InsufficientPointsException;
use App\Exception\RewardNotAvailableException;
use App\Exception\RewardNotFoundException;
use App\Exception\TierRequirementNotMetException;
use App\Repository\LoyaltyAccountRepository;
use App\Repository\LoyaltyRewardRepository;
use App\Repository\LoyaltyTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

final class LoyaltyService
{
    private const POINTS_PER_EURO = 1;

    public function __construct(
        private readonly LoyaltyAccountRepository $accountRepository,
        private readonly LoyaltyTransactionRepository $transactionRepository,
        private readonly LoyaltyRewardRepository $rewardRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getOrCreateAccount(User $user): LoyaltyAccount
    {
        return $this->accountRepository->findOrCreateByUser($user);
    }

    public function awardPointsForOrder(Order $order): LoyaltyTransaction
    {
        $user = $order->getCustomer();
        $account = $this->getOrCreateAccount($user);

        $basePoints = (int) floor((float) $order->getTotalAmount() * self::POINTS_PER_EURO);
        $multiplier = $account->getCurrentMultiplier();
        $points = (int) floor($basePoints * $multiplier);

        $transaction = new LoyaltyTransaction();
        $transaction->setType(LoyaltyTransactionType::EARN);
        $transaction->setPoints($points);
        $transaction->setDescription(\sprintf(
            'Points gagnés pour la commande %s (x%.2f %s)',
            $order->getOrderNumber(),
            $multiplier,
            $account->getTier(),
        ));
        $transaction->setRelatedOrder($order);

        $account->addPoints($points);
        $account->addTransaction($transaction);

        $this->entityManager->flush();

        return $transaction;
    }

    public function redeemReward(User $user, LoyaltyReward $reward): LoyaltyTransaction
    {
        $account = $this->getOrCreateAccount($user);

        if (!$reward->isAvailable()) {
            throw new RewardNotAvailableException($reward->getName());
        }

        if ($account->getPoints() < $reward->getPointsCost()) {
            throw new InsufficientPointsException(
                $reward->getPointsCost(),
                $account->getPoints(),
            );
        }

        $requiredTier = $reward->getRequiredTier();
        if (null !== $requiredTier && !$this->meetsRequiredTier($account->getTier(), $requiredTier)) {
            throw new TierRequirementNotMetException($requiredTier, $account->getTier());
        }

        $transaction = new LoyaltyTransaction();
        $transaction->setType(LoyaltyTransactionType::REDEEM);
        $transaction->setPoints($reward->getPointsCost());
        $transaction->setDescription(\sprintf('Récompense échangée: %s', $reward->getName()));
        $transaction->setRedeemedReward($reward);

        $account->deductPoints($reward->getPointsCost());
        $account->addTransaction($transaction);

        if (null !== $reward->getStockQuantity()) {
            $reward->setStockQuantity($reward->getStockQuantity() - 1);
        }

        $this->entityManager->flush();

        return $transaction;
    }

    public function addBonusPoints(User $user, int $points, string $reason): LoyaltyTransaction
    {
        $account = $this->getOrCreateAccount($user);

        $transaction = new LoyaltyTransaction();
        $transaction->setType(LoyaltyTransactionType::BONUS);
        $transaction->setPoints($points);
        $transaction->setDescription($reason);

        $account->addPoints($points);
        $account->addTransaction($transaction);

        $this->entityManager->flush();

        return $transaction;
    }

    public function adjustPoints(User $user, int $points, string $reason): LoyaltyTransaction
    {
        $account = $this->getOrCreateAccount($user);

        $transaction = new LoyaltyTransaction();
        $transaction->setType(LoyaltyTransactionType::ADJUSTMENT);
        $transaction->setPoints(abs($points));
        $transaction->setDescription($reason);

        if ($points > 0) {
            $account->addPoints($points);
        } else {
            $account->deductPoints(abs($points));
        }
        $account->addTransaction($transaction);

        $this->entityManager->flush();

        return $transaction;
    }

    public function getRewardById(int $id): LoyaltyReward
    {
        $reward = $this->rewardRepository->find($id);

        if (!$reward) {
            throw new RewardNotFoundException($id);
        }

        return $reward;
    }

    /**
     * @return LoyaltyReward[]
     */
    public function getAvailableRewards(): array
    {
        return $this->rewardRepository->findAvailable();
    }

    /**
     * @return LoyaltyReward[]
     */
    public function getAvailableRewardsForUser(User $user): array
    {
        $account = $this->getOrCreateAccount($user);

        return $this->rewardRepository->findAvailableForTier($account->getTier());
    }

    /**
     * @return LoyaltyReward[]
     */
    public function getAffordableRewardsForUser(User $user): array
    {
        $account = $this->getOrCreateAccount($user);

        return $this->rewardRepository->findAffordable($account->getPoints(), $account->getTier());
    }

    /**
     * @return LoyaltyTransaction[]
     */
    public function getTransactionHistory(User $user, ?int $limit = null): array
    {
        $account = $this->getOrCreateAccount($user);

        return $this->transactionRepository->findByAccount($account, $limit);
    }

    public function calculatePointsForAmount(float $amount, float $multiplier = 1.0): int
    {
        $basePoints = (int) floor($amount * self::POINTS_PER_EURO);

        return (int) floor($basePoints * $multiplier);
    }

    public function upgradeTier(User $user): LoyaltyTransaction
    {
        $account = $this->getOrCreateAccount($user);

        $oldTier = $account->getTier();
        $upgradeCost = $account->getUpgradeCost();

        if (null === $upgradeCost) {
            throw new \LogicException('Vous avez déjà atteint le niveau maximum');
        }

        if ($account->getPoints() < $upgradeCost) {
            throw new InsufficientPointsException($upgradeCost, $account->getPoints());
        }

        $account->upgrade();
        $newTier = $account->getTier();

        $transaction = new LoyaltyTransaction();
        $transaction->setType(LoyaltyTransactionType::REDEEM);
        $transaction->setPoints($upgradeCost);
        $transaction->setDescription(\sprintf(
            'Upgrade de carte: %s → %s (x%.2f)',
            $oldTier,
            $newTier,
            $account->getCurrentMultiplier(),
        ));

        $account->addTransaction($transaction);

        $this->entityManager->flush();

        return $transaction;
    }

    private function meetsRequiredTier(string $userTier, string $requiredTier): bool
    {
        $tierOrder = ['bronze' => 0, 'silver' => 1, 'gold' => 2, 'platinum' => 3, 'diamond' => 4];
        $userLevel = $tierOrder[$userTier] ?? 0;
        $requiredLevel = $tierOrder[$requiredTier] ?? 0;

        return $userLevel >= $requiredLevel;
    }
}
