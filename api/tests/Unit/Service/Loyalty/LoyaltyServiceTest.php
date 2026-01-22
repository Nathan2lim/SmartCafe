<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Loyalty;

use App\Entity\LoyaltyAccount;
use App\Entity\LoyaltyReward;
use App\Entity\LoyaltyTransaction;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\LoyaltyTransactionType;
use App\Exception\InsufficientPointsException;
use App\Exception\RewardNotAvailableException;
use App\Exception\TierRequirementNotMetException;
use App\Repository\LoyaltyAccountRepository;
use App\Repository\LoyaltyRewardRepository;
use App\Repository\LoyaltyTransactionRepository;
use App\Service\Loyalty\LoyaltyService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoyaltyServiceTest extends TestCase
{
    private LoyaltyAccountRepository&MockObject $accountRepository;
    private LoyaltyTransactionRepository&MockObject $transactionRepository;
    private LoyaltyRewardRepository&MockObject $rewardRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private LoyaltyService $service;

    protected function setUp(): void
    {
        $this->accountRepository = $this->createMock(LoyaltyAccountRepository::class);
        $this->transactionRepository = $this->createMock(LoyaltyTransactionRepository::class);
        $this->rewardRepository = $this->createMock(LoyaltyRewardRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->service = new LoyaltyService(
            $this->accountRepository,
            $this->transactionRepository,
            $this->rewardRepository,
            $this->entityManager,
        );
    }

    public function testCalculatePointsForAmountDefaultMultiplier(): void
    {
        $points = $this->service->calculatePointsForAmount(10.00);
        $this->assertEquals(10, $points); // 10 * 1 * 1.0
    }

    public function testCalculatePointsForAmountWithMultiplier(): void
    {
        $points = $this->service->calculatePointsForAmount(10.00, 1.5);
        $this->assertEquals(15, $points); // 10 * 1 * 1.5
    }

    public function testCalculatePointsForAmountLargeAmount(): void
    {
        $points = $this->service->calculatePointsForAmount(100.00, 2.0);
        $this->assertEquals(200, $points); // 100 * 1 * 2.0
    }

    public function testCalculatePointsForAmountWithDecimal(): void
    {
        $points = $this->service->calculatePointsForAmount(25.50, 1.25);
        $this->assertEquals(31, $points); // floor(25 * 1.25) = floor(31.25) = 31
    }

    public function testGetOrCreateAccountExisting(): void
    {
        $user = $this->createMock(User::class);
        $account = new LoyaltyAccount();
        $account->setUser($user);

        $this->accountRepository
            ->expects($this->once())
            ->method('findOrCreateByUser')
            ->with($user)
            ->willReturn($account);

        $result = $this->service->getOrCreateAccount($user);

        $this->assertSame($account, $result);
    }

    public function testAwardPointsForOrder(): void
    {
        $user = $this->createMock(User::class);
        $order = $this->createMock(Order::class);
        $account = new LoyaltyAccount();
        $account->setUser($user);

        $order->method('getCustomer')->willReturn($user);
        $order->method('getTotalAmount')->willReturn('25.50');
        $order->method('getOrderNumber')->willReturn('ORD-123');

        $this->accountRepository
            ->method('findOrCreateByUser')
            ->willReturn($account);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $transaction = $this->service->awardPointsForOrder($order);

        $this->assertInstanceOf(LoyaltyTransaction::class, $transaction);
        $this->assertEquals(LoyaltyTransactionType::EARN, $transaction->getType());
        $this->assertEquals(25, $transaction->getPoints()); // floor(25.50 * 1) * 1.0
        $this->assertGreaterThan(0, $account->getPoints());
    }

    public function testRedeemRewardSuccess(): void
    {
        $user = $this->createMock(User::class);
        $account = new LoyaltyAccount();
        $account->setUser($user);
        $account->addPoints(500);

        $reward = new LoyaltyReward();
        $reward->setName('Café gratuit');
        $reward->setPointsCost(100);
        $reward->setActive(true);

        $this->accountRepository
            ->method('findOrCreateByUser')
            ->willReturn($account);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $transaction = $this->service->redeemReward($user, $reward);

        $this->assertEquals(LoyaltyTransactionType::REDEEM, $transaction->getType());
        $this->assertEquals(100, $transaction->getPoints());
        $this->assertEquals(400, $account->getPoints());
    }

    public function testRedeemRewardInsufficientPoints(): void
    {
        $user = $this->createMock(User::class);
        $account = new LoyaltyAccount();
        $account->setUser($user);
        $account->addPoints(50);

        $reward = new LoyaltyReward();
        $reward->setName('Café gratuit');
        $reward->setPointsCost(100);
        $reward->setActive(true);

        $this->accountRepository
            ->method('findOrCreateByUser')
            ->willReturn($account);

        $this->expectException(InsufficientPointsException::class);

        $this->service->redeemReward($user, $reward);
    }

    public function testRedeemRewardNotAvailable(): void
    {
        $user = $this->createMock(User::class);
        $account = new LoyaltyAccount();
        $account->setUser($user);
        $account->addPoints(500);

        $reward = new LoyaltyReward();
        $reward->setName('Café gratuit');
        $reward->setPointsCost(100);
        $reward->setActive(false);

        $this->accountRepository
            ->method('findOrCreateByUser')
            ->willReturn($account);

        $this->expectException(RewardNotAvailableException::class);

        $this->service->redeemReward($user, $reward);
    }

    public function testRedeemRewardTierRequirementNotMet(): void
    {
        $user = $this->createMock(User::class);
        $account = new LoyaltyAccount();
        $account->setUser($user);
        $account->addPoints(500);

        $reward = new LoyaltyReward();
        $reward->setName('Récompense Gold');
        $reward->setPointsCost(100);
        $reward->setActive(true);
        $reward->setRequiredTier('gold');

        $this->accountRepository
            ->method('findOrCreateByUser')
            ->willReturn($account);

        $this->expectException(TierRequirementNotMetException::class);

        $this->service->redeemReward($user, $reward);
    }

    public function testAddBonusPoints(): void
    {
        $user = $this->createMock(User::class);
        $account = new LoyaltyAccount();
        $account->setUser($user);

        $this->accountRepository
            ->method('findOrCreateByUser')
            ->willReturn($account);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $transaction = $this->service->addBonusPoints($user, 100, 'Bonus bienvenue');

        $this->assertEquals(LoyaltyTransactionType::BONUS, $transaction->getType());
        $this->assertEquals(100, $transaction->getPoints());
        $this->assertEquals(100, $account->getPoints());
    }
}
