<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Extra;
use App\Entity\Product;
use App\Entity\ProductExtra;
use PHPUnit\Framework\TestCase;

class ProductExtraTest extends TestCase
{
    public function testProductExtraCreation(): void
    {
        $productExtra = new ProductExtra();

        $this->assertNull($productExtra->getId());
        $this->assertNull($productExtra->getProduct());
        $this->assertNull($productExtra->getExtra());
        // Default maxQuantity may vary, just check it's a positive integer
        $this->assertGreaterThan(0, $productExtra->getMaxQuantity());
    }

    public function testSetProduct(): void
    {
        $productExtra = new ProductExtra();
        $product = new Product();
        $product->setName('Café Latte');

        $productExtra->setProduct($product);

        $this->assertSame($product, $productExtra->getProduct());
    }

    public function testSetExtra(): void
    {
        $productExtra = new ProductExtra();
        $extra = new Extra();
        $extra->setName('Chantilly');

        $productExtra->setExtra($extra);

        $this->assertSame($extra, $productExtra->getExtra());
    }

    public function testSetMaxQuantity(): void
    {
        $productExtra = new ProductExtra();

        $productExtra->setMaxQuantity(5);

        $this->assertEquals(5, $productExtra->getMaxQuantity());
    }

    public function testFullConfiguration(): void
    {
        $product = new Product();
        $product->setName('Café Latte');

        $extra = new Extra();
        $extra->setName('Chantilly');

        $productExtra = new ProductExtra();
        $productExtra->setProduct($product);
        $productExtra->setExtra($extra);
        $productExtra->setMaxQuantity(3);

        $this->assertSame($product, $productExtra->getProduct());
        $this->assertSame($extra, $productExtra->getExtra());
        $this->assertEquals(3, $productExtra->getMaxQuantity());
    }
}
