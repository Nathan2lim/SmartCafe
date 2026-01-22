<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\LoyaltyReward;
use App\Entity\Product;
use App\Enum\RewardType;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class LoyaltyRewardTest extends TestCase
{
    public function testLoyaltyRewardCreation(): void
    {
        $reward = new LoyaltyReward();

        $this->assertNull($reward->getId());
        $this->assertTrue($reward->isActive());
        $this->assertNull($reward->getStockQuantity());
        $this->assertNotNull($reward->getCreatedAt());
    }

    public function testSetName(): void
    {
        $reward = new LoyaltyReward();

        $reward->setName('Café gratuit');

        $this->assertEquals('Café gratuit', $reward->getName());
    }

    public function testSetDescription(): void
    {
        $reward = new LoyaltyReward();

        $reward->setDescription('Un café offert de votre choix');

        $this->assertEquals('Un café offert de votre choix', $reward->getDescription());
    }

    public function testSetPointsCost(): void
    {
        $reward = new LoyaltyReward();

        $reward->setPointsCost(500);

        $this->assertEquals(500, $reward->getPointsCost());
    }

    public function testSetType(): void
    {
        $reward = new LoyaltyReward();

        $reward->setType(RewardType::FREE_PRODUCT);

        $this->assertEquals(RewardType::FREE_PRODUCT, $reward->getType());
    }

    public function testSetDiscountValue(): void
    {
        $reward = new LoyaltyReward();

        $reward->setDiscountValue('5.00');

        $this->assertEquals('5.00', $reward->getDiscountValue());
    }

    public function testSetDiscountPercent(): void
    {
        $reward = new LoyaltyReward();

        $reward->setDiscountPercent(10);

        $this->assertEquals(10, $reward->getDiscountPercent());
    }

    public function testSetFreeProduct(): void
    {
        $reward = new LoyaltyReward();
        $product = new Product();
        $product->setName('Café Latte');

        $reward->setFreeProduct($product);

        $this->assertSame($product, $reward->getFreeProduct());
    }

    public function testSetRequiredTier(): void
    {
        $reward = new LoyaltyReward();

        $reward->setRequiredTier('gold');

        $this->assertEquals('gold', $reward->getRequiredTier());
    }

    public function testSetActive(): void
    {
        $reward = new LoyaltyReward();

        $reward->setActive(false);

        $this->assertFalse($reward->isActive());
    }

    public function testSetStockQuantity(): void
    {
        $reward = new LoyaltyReward();

        $reward->setStockQuantity(50);

        $this->assertEquals(50, $reward->getStockQuantity());
    }

    public function testSetStockQuantityNull(): void
    {
        $reward = new LoyaltyReward();
        $reward->setStockQuantity(50);

        $reward->setStockQuantity(null);

        $this->assertNull($reward->getStockQuantity());
    }

    public function testSetUpdatedAt(): void
    {
        $reward = new LoyaltyReward();
        $now = new DateTimeImmutable();

        $reward->setUpdatedAt($now);

        $this->assertEquals($now, $reward->getUpdatedAt());
    }

    public function testFreeProductReward(): void
    {
        $product = new Product();
        $product->setName('Croissant');
        $product->setPrice('2.50');

        $reward = new LoyaltyReward();
        $reward->setName('Croissant gratuit');
        $reward->setType(RewardType::FREE_PRODUCT);
        $reward->setPointsCost(200);
        $reward->setFreeProduct($product);

        $this->assertEquals(RewardType::FREE_PRODUCT, $reward->getType());
        $this->assertSame($product, $reward->getFreeProduct());
    }

    public function testDiscountAmountReward(): void
    {
        $reward = new LoyaltyReward();
        $reward->setName('5€ de réduction');
        $reward->setType(RewardType::DISCOUNT_AMOUNT);
        $reward->setPointsCost(400);
        $reward->setDiscountValue('5.00');

        $this->assertEquals(RewardType::DISCOUNT_AMOUNT, $reward->getType());
        $this->assertEquals('5.00', $reward->getDiscountValue());
    }

    public function testDiscountPercentReward(): void
    {
        $reward = new LoyaltyReward();
        $reward->setName('10% de réduction');
        $reward->setType(RewardType::DISCOUNT_PERCENT);
        $reward->setPointsCost(300);
        $reward->setDiscountPercent(10);

        $this->assertEquals(RewardType::DISCOUNT_PERCENT, $reward->getType());
        $this->assertEquals(10, $reward->getDiscountPercent());
    }
}
