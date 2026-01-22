<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\LoyaltyAccount;
use App\Entity\LoyaltyReward;
use App\Entity\LoyaltyTransaction;
use App\Entity\Order;
use App\Enum\LoyaltyTransactionType;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class LoyaltyTransactionTest extends TestCase
{
    public function testLoyaltyTransactionCreation(): void
    {
        $transaction = new LoyaltyTransaction();

        $this->assertNull($transaction->getId());
        $this->assertNotNull($transaction->getCreatedAt());
    }

    public function testSetAccount(): void
    {
        $transaction = new LoyaltyTransaction();
        $account = new LoyaltyAccount();

        $transaction->setAccount($account);

        $this->assertSame($account, $transaction->getAccount());
    }

    public function testSetType(): void
    {
        $transaction = new LoyaltyTransaction();

        $transaction->setType(LoyaltyTransactionType::EARN);

        $this->assertEquals(LoyaltyTransactionType::EARN, $transaction->getType());
    }

    public function testSetPoints(): void
    {
        $transaction = new LoyaltyTransaction();

        $transaction->setPoints(100);

        $this->assertEquals(100, $transaction->getPoints());
    }

    public function testSetNegativePoints(): void
    {
        $transaction = new LoyaltyTransaction();

        $transaction->setPoints(-50);

        $this->assertEquals(-50, $transaction->getPoints());
    }

    public function testSetDescription(): void
    {
        $transaction = new LoyaltyTransaction();

        $transaction->setDescription('Points gagnés pour commande #123');

        $this->assertEquals('Points gagnés pour commande #123', $transaction->getDescription());
    }

    public function testSetRelatedOrder(): void
    {
        $transaction = new LoyaltyTransaction();
        $order = new Order();

        $transaction->setRelatedOrder($order);

        $this->assertSame($order, $transaction->getRelatedOrder());
    }

    public function testSetRelatedOrderNull(): void
    {
        $transaction = new LoyaltyTransaction();
        $order = new Order();
        $transaction->setRelatedOrder($order);

        $transaction->setRelatedOrder(null);

        $this->assertNull($transaction->getRelatedOrder());
    }

    public function testSetRedeemedReward(): void
    {
        $transaction = new LoyaltyTransaction();
        $reward = new LoyaltyReward();
        $reward->setName('Café gratuit');

        $transaction->setRedeemedReward($reward);

        $this->assertSame($reward, $transaction->getRedeemedReward());
    }

    public function testSetRedeemedRewardNull(): void
    {
        $transaction = new LoyaltyTransaction();
        $reward = new LoyaltyReward();
        $transaction->setRedeemedReward($reward);

        $transaction->setRedeemedReward(null);

        $this->assertNull($transaction->getRedeemedReward());
    }

    public function testEarnTransaction(): void
    {
        $account = new LoyaltyAccount();
        $order = new Order();

        $transaction = new LoyaltyTransaction();
        $transaction->setAccount($account);
        $transaction->setType(LoyaltyTransactionType::EARN);
        $transaction->setPoints(150);
        $transaction->setRelatedOrder($order);
        $transaction->setDescription('Points gagnés pour commande');

        $this->assertEquals(LoyaltyTransactionType::EARN, $transaction->getType());
        $this->assertEquals(150, $transaction->getPoints());
        $this->assertSame($order, $transaction->getRelatedOrder());
    }

    public function testRedeemTransaction(): void
    {
        $account = new LoyaltyAccount();
        $reward = new LoyaltyReward();
        $reward->setName('Café gratuit');
        $reward->setPointsCost(500);

        $transaction = new LoyaltyTransaction();
        $transaction->setAccount($account);
        $transaction->setType(LoyaltyTransactionType::REDEEM);
        $transaction->setPoints(-500);
        $transaction->setRedeemedReward($reward);
        $transaction->setDescription('Échange contre Café gratuit');

        $this->assertEquals(LoyaltyTransactionType::REDEEM, $transaction->getType());
        $this->assertEquals(-500, $transaction->getPoints());
        $this->assertSame($reward, $transaction->getRedeemedReward());
    }

    public function testBonusTransaction(): void
    {
        $account = new LoyaltyAccount();

        $transaction = new LoyaltyTransaction();
        $transaction->setAccount($account);
        $transaction->setType(LoyaltyTransactionType::BONUS);
        $transaction->setPoints(50);
        $transaction->setDescription('Bonus de bienvenue');

        $this->assertEquals(LoyaltyTransactionType::BONUS, $transaction->getType());
        $this->assertEquals(50, $transaction->getPoints());
    }

    public function testAdjustmentTransaction(): void
    {
        $account = new LoyaltyAccount();

        $transaction = new LoyaltyTransaction();
        $transaction->setAccount($account);
        $transaction->setType(LoyaltyTransactionType::ADJUSTMENT);
        $transaction->setPoints(25);
        $transaction->setDescription('Ajustement manuel');

        $this->assertEquals(LoyaltyTransactionType::ADJUSTMENT, $transaction->getType());
    }

    public function testSetCreatedAt(): void
    {
        $transaction = new LoyaltyTransaction();
        $date = new DateTimeImmutable('2024-01-01');

        $transaction->setCreatedAt($date);

        $this->assertEquals($date, $transaction->getCreatedAt());
    }
}
