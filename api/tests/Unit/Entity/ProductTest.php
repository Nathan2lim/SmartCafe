<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Product;
use App\Entity\ProductExtra;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testProductCreation(): void
    {
        $product = new Product();

        $this->assertNull($product->getId());
        $this->assertTrue($product->isAvailable());
        $this->assertFalse($product->isAlaCarte());
        $this->assertNull($product->getStockQuantity());
        $this->assertEquals(10, $product->getLowStockThreshold());
        $this->assertNotNull($product->getCreatedAt());
        $this->assertCount(0, $product->getOrderItems());
        $this->assertCount(0, $product->getAvailableExtras());
    }

    public function testSetName(): void
    {
        $product = new Product();

        $product->setName('Café Latte');

        $this->assertEquals('Café Latte', $product->getName());
    }

    public function testSetDescription(): void
    {
        $product = new Product();

        $product->setDescription('Délicieux café avec du lait');

        $this->assertEquals('Délicieux café avec du lait', $product->getDescription());
    }

    public function testSetPrice(): void
    {
        $product = new Product();

        $product->setPrice('4.50');

        $this->assertEquals('4.50', $product->getPrice());
    }

    public function testSetCategory(): void
    {
        $product = new Product();

        $product->setCategory('Boissons chaudes');

        $this->assertEquals('Boissons chaudes', $product->getCategory());
    }

    public function testSetAvailable(): void
    {
        $product = new Product();

        $product->setAvailable(false);

        $this->assertFalse($product->isAvailable());
    }

    public function testSetAlaCarte(): void
    {
        $product = new Product();

        $product->setAlaCarte(true);

        $this->assertTrue($product->isAlaCarte());
    }

    public function testSetImageUrl(): void
    {
        $product = new Product();

        $product->setImageUrl('https://example.com/cafe.jpg');

        $this->assertEquals('https://example.com/cafe.jpg', $product->getImageUrl());
    }

    public function testSetStockQuantity(): void
    {
        $product = new Product();

        $product->setStockQuantity(100);

        $this->assertEquals(100, $product->getStockQuantity());
    }

    public function testSetStockQuantityNull(): void
    {
        $product = new Product();
        $product->setStockQuantity(100);

        $product->setStockQuantity(null);

        $this->assertNull($product->getStockQuantity());
    }

    public function testSetLowStockThreshold(): void
    {
        $product = new Product();

        $product->setLowStockThreshold(20);

        $this->assertEquals(20, $product->getLowStockThreshold());
    }

    public function testIsLowStockTrue(): void
    {
        $product = new Product();
        $product->setStockQuantity(5);
        $product->setLowStockThreshold(10);

        $this->assertTrue($product->isLowStock());
    }

    public function testIsLowStockFalse(): void
    {
        $product = new Product();
        $product->setStockQuantity(50);
        $product->setLowStockThreshold(10);

        $this->assertFalse($product->isLowStock());
    }

    public function testIsLowStockAtThreshold(): void
    {
        $product = new Product();
        $product->setStockQuantity(10);
        $product->setLowStockThreshold(10);

        $this->assertTrue($product->isLowStock());
    }

    public function testIsLowStockWithNullStock(): void
    {
        $product = new Product();
        $product->setStockQuantity(null);

        $this->assertFalse($product->isLowStock());
    }

    public function testSetUpdatedAt(): void
    {
        $product = new Product();
        $now = new \DateTimeImmutable();

        $product->setUpdatedAt($now);

        $this->assertEquals($now, $product->getUpdatedAt());
    }

    public function testAddAvailableExtra(): void
    {
        $product = new Product();
        $productExtra = new ProductExtra();

        $product->addAvailableExtra($productExtra);

        $this->assertCount(1, $product->getAvailableExtras());
        $this->assertSame($product, $productExtra->getProduct());
    }

    public function testAddAvailableExtraDoesNotDuplicate(): void
    {
        $product = new Product();
        $productExtra = new ProductExtra();

        $product->addAvailableExtra($productExtra);
        $product->addAvailableExtra($productExtra);

        $this->assertCount(1, $product->getAvailableExtras());
    }

    public function testRemoveAvailableExtra(): void
    {
        $product = new Product();
        $productExtra = new ProductExtra();

        $product->addAvailableExtra($productExtra);
        $this->assertCount(1, $product->getAvailableExtras());

        $product->removeAvailableExtra($productExtra);
        $this->assertCount(0, $product->getAvailableExtras());
        $this->assertNull($productExtra->getProduct());
    }
}
