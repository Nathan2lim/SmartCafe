<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\LoyaltyAccount;
use PHPUnit\Framework\TestCase;

class LoyaltyAccountTest extends TestCase
{
    public function testNewAccountStartsWithZeroPoints(): void
    {
        $account = new LoyaltyAccount();

        $this->assertEquals(0, $account->getPoints());
        $this->assertEquals(0, $account->getTotalPointsEarned());
        $this->assertEquals(0, $account->getTotalPointsSpent());
        $this->assertEquals('bronze', $account->getTier());
    }

    public function testAddPoints(): void
    {
        $account = new LoyaltyAccount();
        $account->addPoints(100);

        $this->assertEquals(100, $account->getPoints());
        $this->assertEquals(100, $account->getTotalPointsEarned());
    }

    public function testDeductPoints(): void
    {
        $account = new LoyaltyAccount();
        $account->addPoints(200);
        $account->deductPoints(50);

        $this->assertEquals(150, $account->getPoints());
        $this->assertEquals(50, $account->getTotalPointsSpent());
    }

    public function testTierUpgradeToSilver(): void
    {
        $account = new LoyaltyAccount();
        $account->addPoints(500);

        $this->assertEquals('silver', $account->getTier());
    }

    public function testTierUpgradeToGold(): void
    {
        $account = new LoyaltyAccount();
        $account->addPoints(2000);

        $this->assertEquals('gold', $account->getTier());
    }

    public function testTierUpgradeToPlatinum(): void
    {
        $account = new LoyaltyAccount();
        $account->addPoints(5000);

        $this->assertEquals('platinum', $account->getTier());
    }

    public function testPointsToNextTierBronze(): void
    {
        $account = new LoyaltyAccount();
        $account->addPoints(200);

        $this->assertEquals(300, $account->getPointsToNextTier()); // 500 - 200
        $this->assertEquals('silver', $account->getNextTier());
    }

    public function testPointsToNextTierSilver(): void
    {
        $account = new LoyaltyAccount();
        $account->addPoints(1000);

        $this->assertEquals(1000, $account->getPointsToNextTier()); // 2000 - 1000
        $this->assertEquals('gold', $account->getNextTier());
    }

    public function testPointsToNextTierPlatinum(): void
    {
        $account = new LoyaltyAccount();
        $account->addPoints(5000);

        $this->assertEquals(0, $account->getPointsToNextTier());
        $this->assertNull($account->getNextTier());
    }
}
