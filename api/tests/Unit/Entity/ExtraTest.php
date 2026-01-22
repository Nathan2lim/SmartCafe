<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Extra;
use PHPUnit\Framework\TestCase;

class ExtraTest extends TestCase
{
    public function testExtraCreation(): void
    {
        $extra = new Extra();

        $this->assertNull($extra->getId());
        $this->assertTrue($extra->isAvailable());
        $this->assertEquals(0, $extra->getStockQuantity());
        $this->assertEquals(10, $extra->getLowStockThreshold());
        $this->assertNotNull($extra->getCreatedAt());
        $this->assertCount(0, $extra->getProductExtras());
        $this->assertCount(0, $extra->getOrderItemExtras());
    }

    public function testSetName(): void
    {
        $extra = new Extra();

        $extra->setName('Chantilly');

        $this->assertEquals('Chantilly', $extra->getName());
    }

    public function testSetDescription(): void
    {
        $extra = new Extra();

        $extra->setDescription('Crème fouettée délicieuse');

        $this->assertEquals('Crème fouettée délicieuse', $extra->getDescription());
    }

    public function testSetPrice(): void
    {
        $extra = new Extra();

        $extra->setPrice('0.50');

        $this->assertEquals('0.50', $extra->getPrice());
    }

    public function testSetStockQuantity(): void
    {
        $extra = new Extra();

        $extra->setStockQuantity(100);

        $this->assertEquals(100, $extra->getStockQuantity());
    }

    public function testSetLowStockThreshold(): void
    {
        $extra = new Extra();

        $extra->setLowStockThreshold(15);

        $this->assertEquals(15, $extra->getLowStockThreshold());
    }

    public function testSetAvailable(): void
    {
        $extra = new Extra();

        $extra->setAvailable(false);

        $this->assertFalse($extra->isAvailable());
    }

    public function testIsLowStockTrue(): void
    {
        $extra = new Extra();
        $extra->setStockQuantity(5);
        $extra->setLowStockThreshold(10);

        $this->assertTrue($extra->isLowStock());
    }

    public function testIsLowStockFalse(): void
    {
        $extra = new Extra();
        $extra->setStockQuantity(50);
        $extra->setLowStockThreshold(10);

        $this->assertFalse($extra->isLowStock());
    }

    public function testIsLowStockAtThreshold(): void
    {
        $extra = new Extra();
        $extra->setStockQuantity(10);
        $extra->setLowStockThreshold(10);

        $this->assertTrue($extra->isLowStock());
    }

    public function testIsLowStockWithZeroStock(): void
    {
        $extra = new Extra();
        $extra->setStockQuantity(0);
        $extra->setLowStockThreshold(10);

        $this->assertTrue($extra->isLowStock());
    }

    public function testSetUpdatedAt(): void
    {
        $extra = new Extra();
        $now = new \DateTimeImmutable();

        $extra->setUpdatedAt($now);

        $this->assertEquals($now, $extra->getUpdatedAt());
    }

    public function testSetCreatedAt(): void
    {
        $extra = new Extra();
        $now = new \DateTimeImmutable('2024-01-01');

        $extra->setCreatedAt($now);

        $this->assertEquals($now, $extra->getCreatedAt());
    }
}
