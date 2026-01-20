<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Stock;

use App\Entity\Extra;
use App\Entity\Product;
use App\Exception\InsufficientStockException;
use App\Repository\ExtraRepository;
use App\Repository\ProductRepository;
use App\Service\Stock\StockService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StockServiceTest extends TestCase
{
    private ProductRepository&MockObject $productRepository;
    private ExtraRepository&MockObject $extraRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private StockService $service;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->extraRepository = $this->createMock(ExtraRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->service = new StockService(
            $this->productRepository,
            $this->extraRepository,
            $this->entityManager,
        );
    }

    public function testCheckProductAvailabilityWithStock(): void
    {
        $product = new Product();
        $product->setName('Café');
        $product->setStockQuantity(10);

        $this->assertTrue($this->service->checkProductAvailability($product, 5));
        $this->assertTrue($this->service->checkProductAvailability($product, 10));
        $this->assertFalse($this->service->checkProductAvailability($product, 11));
    }

    public function testCheckProductAvailabilityWithoutStock(): void
    {
        $product = new Product();
        $product->setName('Café');
        $product->setStockQuantity(null);

        // Null stock = unlimited
        $this->assertTrue($this->service->checkProductAvailability($product, 1000));
    }

    public function testDeductProductStock(): void
    {
        $product = new Product();
        $product->setName('Café');
        $product->setStockQuantity(10);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->deductProductStock($product, 3);

        $this->assertEquals(7, $product->getStockQuantity());
    }

    public function testDeductProductStockInsufficientStock(): void
    {
        $product = new Product();
        $product->setName('Café');
        $product->setStockQuantity(2);

        $this->expectException(InsufficientStockException::class);

        $this->service->deductProductStock($product, 5);
    }

    public function testRestockProduct(): void
    {
        $product = new Product();
        $product->setName('Café');
        $product->setStockQuantity(5);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->restockProduct($product, 10);

        $this->assertEquals(15, $product->getStockQuantity());
    }

    public function testCheckExtraAvailability(): void
    {
        $extra = new Extra();
        $extra->setName('Chantilly');
        $extra->setStockQuantity(20);

        $this->assertTrue($this->service->checkExtraAvailability($extra, 10));
        $this->assertTrue($this->service->checkExtraAvailability($extra, 20));
        $this->assertFalse($this->service->checkExtraAvailability($extra, 21));
    }

    public function testDeductExtraStock(): void
    {
        $extra = new Extra();
        $extra->setName('Chantilly');
        $extra->setStockQuantity(20);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->service->deductExtraStock($extra, 5);

        $this->assertEquals(15, $extra->getStockQuantity());
    }

    public function testIsLowStockProduct(): void
    {
        $product = new Product();
        $product->setName('Café');
        $product->setLowStockThreshold(10);
        $product->setStockQuantity(5);

        $this->assertTrue($this->service->isLowStock($product));

        $product->setStockQuantity(15);
        $this->assertFalse($this->service->isLowStock($product));
    }

    public function testIsLowStockExtra(): void
    {
        $extra = new Extra();
        $extra->setName('Chantilly');
        $extra->setLowStockThreshold(10);
        $extra->setStockQuantity(5);

        $this->assertTrue($this->service->isLowStock($extra));

        $extra->setStockQuantity(15);
        $this->assertFalse($this->service->isLowStock($extra));
    }
}
