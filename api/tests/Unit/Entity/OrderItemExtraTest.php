<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Extra;
use App\Entity\OrderItem;
use App\Entity\OrderItemExtra;
use PHPUnit\Framework\TestCase;

class OrderItemExtraTest extends TestCase
{
    public function testOrderItemExtraCreation(): void
    {
        $orderItemExtra = new OrderItemExtra();

        $this->assertNull($orderItemExtra->getId());
        $this->assertNull($orderItemExtra->getOrderItem());
        $this->assertNull($orderItemExtra->getExtra());
        $this->assertEquals(1, $orderItemExtra->getQuantity());
    }

    public function testSetOrderItem(): void
    {
        $orderItemExtra = new OrderItemExtra();
        $orderItem = new OrderItem();

        $orderItemExtra->setOrderItem($orderItem);

        $this->assertSame($orderItem, $orderItemExtra->getOrderItem());
    }

    public function testSetExtra(): void
    {
        $orderItemExtra = new OrderItemExtra();
        $extra = new Extra();
        $extra->setName('Chantilly');
        $extra->setPrice('0.50');

        $orderItemExtra->setExtra($extra);

        $this->assertSame($extra, $orderItemExtra->getExtra());
        $this->assertEquals('0.50', $orderItemExtra->getUnitPrice());
    }

    public function testSetQuantity(): void
    {
        $orderItemExtra = new OrderItemExtra();

        $orderItemExtra->setQuantity(3);

        $this->assertEquals(3, $orderItemExtra->getQuantity());
    }

    public function testUnitPriceSetFromExtra(): void
    {
        $extra = new Extra();
        $extra->setName('Sirop Caramel');
        $extra->setPrice('0.75');

        $orderItemExtra = new OrderItemExtra();
        $orderItemExtra->setExtra($extra);

        $this->assertEquals('0.75', $orderItemExtra->getUnitPrice());
    }

    public function testGetSubtotal(): void
    {
        $extra = new Extra();
        $extra->setName('Chantilly');
        $extra->setPrice('0.50');

        $orderItemExtra = new OrderItemExtra();
        $orderItemExtra->setExtra($extra);
        $orderItemExtra->setQuantity(2);

        $this->assertEquals('1.00', $orderItemExtra->getSubtotal());
    }

    public function testGetSubtotalWithHighQuantity(): void
    {
        $extra = new Extra();
        $extra->setName('Shot Espresso');
        $extra->setPrice('1.00');

        $orderItemExtra = new OrderItemExtra();
        $orderItemExtra->setExtra($extra);
        $orderItemExtra->setQuantity(3);

        $this->assertEquals('3.00', $orderItemExtra->getSubtotal());
    }
}
