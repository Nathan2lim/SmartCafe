<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Extra;
use App\Entity\OrderItem;
use App\Entity\OrderItemExtra;
use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class OrderItemTest extends TestCase
{
    public function testSubtotalWithoutExtras(): void
    {
        $product = new Product();
        $product->setPrice('5.00');

        $item = new OrderItem();
        $item->setProduct($product);
        $item->setQuantity(2);

        $this->assertEquals('10.00', $item->getSubtotal());
    }

    public function testSubtotalWithExtras(): void
    {
        $product = new Product();
        $product->setPrice('5.00');

        $extra = new Extra();
        $extra->setPrice('1.00');

        $orderItemExtra = new OrderItemExtra();
        $orderItemExtra->setExtra($extra);
        $orderItemExtra->setQuantity(2);

        $item = new OrderItem();
        $item->setProduct($product);
        $item->setQuantity(2);
        $item->addExtra($orderItemExtra);

        // Product: 5.00 * 2 = 10.00
        // Extra: 1.00 * 2 = 2.00
        // Total: 12.00
        $this->assertEquals('12.00', $item->getSubtotal());
    }

    public function testSubtotalWithMultipleExtras(): void
    {
        $product = new Product();
        $product->setPrice('4.50');

        $extra1 = new Extra();
        $extra1->setPrice('0.50');

        $extra2 = new Extra();
        $extra2->setPrice('1.00');

        $orderItemExtra1 = new OrderItemExtra();
        $orderItemExtra1->setExtra($extra1);
        $orderItemExtra1->setQuantity(1);

        $orderItemExtra2 = new OrderItemExtra();
        $orderItemExtra2->setExtra($extra2);
        $orderItemExtra2->setQuantity(2);

        $item = new OrderItem();
        $item->setProduct($product);
        $item->setQuantity(3);
        $item->addExtra($orderItemExtra1);
        $item->addExtra($orderItemExtra2);

        // Product: 4.50 * 3 = 13.50
        // Extra1: 0.50 * 1 = 0.50
        // Extra2: 1.00 * 2 = 2.00
        // Total: 16.00
        $this->assertEquals('16.00', $item->getSubtotal());
    }

    public function testAddAndRemoveExtra(): void
    {
        $item = new OrderItem();
        $extra = new OrderItemExtra();

        $item->addExtra($extra);
        $this->assertCount(1, $item->getExtras());

        $item->removeExtra($extra);
        $this->assertCount(0, $item->getExtras());
    }
}
