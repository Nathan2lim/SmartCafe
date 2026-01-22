<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\LoyaltyAccount;
use App\Entity\User;
use DateTimeImmutable;
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

    public function testSetTier(): void
    {
        $account = new LoyaltyAccount();

        $account->setTier('silver');

        $this->assertEquals('silver', $account->getTier());
    }

    public function testCanUpgradeWhenEnoughPoints(): void
    {
        $account = new LoyaltyAccount();
        $account->addPoints(50); // Bronze upgrade cost is 50

        $this->assertTrue($account->canUpgrade());
    }

    public function testCannotUpgradeWhenNotEnoughPoints(): void
    {
        $account = new LoyaltyAccount();
        $account->addPoints(10);

        $this->assertFalse($account->canUpgrade());
    }

    public function testUpgrade(): void
    {
        $account = new LoyaltyAccount();
        $account->addPoints(50);

        $result = $account->upgrade();

        $this->assertTrue($result);
        $this->assertEquals('silver', $account->getTier());
        $this->assertEquals(0, $account->getPoints()); // Points reset after upgrade
    }

    public function testUpgradeFailsWhenNotEnoughPoints(): void
    {
        $account = new LoyaltyAccount();
        $account->addPoints(10);

        $result = $account->upgrade();

        $this->assertFalse($result);
        $this->assertEquals('bronze', $account->getTier());
    }

    public function testGetNextTier(): void
    {
        $account = new LoyaltyAccount();

        $this->assertEquals('silver', $account->getNextTier());

        $account->setTier('silver');
        $this->assertEquals('gold', $account->getNextTier());

        $account->setTier('diamond');
        $this->assertNull($account->getNextTier());
    }

    public function testGetUpgradeCost(): void
    {
        $account = new LoyaltyAccount();

        $this->assertEquals(50, $account->getUpgradeCost()); // Bronze

        $account->setTier('silver');
        $this->assertEquals(150, $account->getUpgradeCost());

        $account->setTier('diamond');
        $this->assertNull($account->getUpgradeCost()); // Max tier
    }

    public function testGetPointsToUpgrade(): void
    {
        $account = new LoyaltyAccount();
        $account->addPoints(30);

        $this->assertEquals(20, $account->getPointsToUpgrade()); // 50 - 30
    }

    public function testGetPointsToUpgradeMaxTier(): void
    {
        $account = new LoyaltyAccount();
        $account->setTier('diamond');

        $this->assertEquals(0, $account->getPointsToUpgrade());
    }

    public function testGetCurrentMultiplier(): void
    {
        $account = new LoyaltyAccount();

        $this->assertEquals(1.0, $account->getCurrentMultiplier()); // Bronze

        $account->setTier('silver');
        $this->assertEquals(1.10, $account->getCurrentMultiplier());

        $account->setTier('gold');
        $this->assertEquals(1.25, $account->getCurrentMultiplier());

        $account->setTier('diamond');
        $this->assertEquals(2.0, $account->getCurrentMultiplier());
    }

    public function testSetUser(): void
    {
        $account = new LoyaltyAccount();
        $user = new User();
        $user->setEmail('test@example.com');

        $account->setUser($user);

        $this->assertSame($user, $account->getUser());
    }

    public function testCreatedAt(): void
    {
        $account = new LoyaltyAccount();

        $this->assertNotNull($account->getCreatedAt());
    }

    public function testSetUpdatedAt(): void
    {
        $account = new LoyaltyAccount();
        $date = new DateTimeImmutable();

        $account->setUpdatedAt($date);

        $this->assertEquals($date, $account->getUpdatedAt());
    }
}
