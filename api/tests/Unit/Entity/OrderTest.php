<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    public function testOrderCreation(): void
    {
        $order = new Order();

        $this->assertNull($order->getId());
        $this->assertNotNull($order->getOrderNumber());
        $this->assertStringStartsWith('ORD-', $order->getOrderNumber());
        $this->assertEquals(OrderStatus::PENDING, $order->getStatus());
        $this->assertEquals('0.00', $order->getTotalAmount());
        $this->assertNotNull($order->getCreatedAt());
        $this->assertCount(0, $order->getItems());
    }

    public function testOrderNumberFormat(): void
    {
        $order = new Order();
        $orderNumber = $order->getOrderNumber();

        // Format: ORD-YYYYMMDD-XXXXXXXX
        $this->assertMatchesRegularExpression(
            '/^ORD-\d{8}-[A-F0-9]{8}$/i',
            $orderNumber,
        );
    }

    public function testSetCustomer(): void
    {
        $order = new Order();
        $user = new User();
        $user->setEmail('test@example.com');

        $order->setCustomer($user);

        $this->assertSame($user, $order->getCustomer());
    }

    public function testSetStatus(): void
    {
        $order = new Order();

        $order->setStatus(OrderStatus::CONFIRMED);

        $this->assertEquals(OrderStatus::CONFIRMED, $order->getStatus());
    }

    public function testAddItem(): void
    {
        $order = new Order();
        $product = new Product();
        $product->setName('Café');
        $product->setPrice('4.50');

        $item = new OrderItem();
        $item->setProduct($product);
        $item->setQuantity(2);

        $order->addItem($item);

        $this->assertCount(1, $order->getItems());
        $this->assertSame($order, $item->getOrder());
    }

    public function testAddItemDoesNotDuplicate(): void
    {
        $order = new Order();
        $item = new OrderItem();

        $order->addItem($item);
        $order->addItem($item);

        $this->assertCount(1, $order->getItems());
    }

    public function testRemoveItem(): void
    {
        $order = new Order();
        $item = new OrderItem();

        $order->addItem($item);
        $this->assertCount(1, $order->getItems());

        $order->removeItem($item);
        $this->assertCount(0, $order->getItems());
        $this->assertNull($item->getOrder());
    }

    public function testCalculateTotal(): void
    {
        $order = new Order();

        $product1 = new Product();
        $product1->setName('Café');
        $product1->setPrice('4.50');

        $product2 = new Product();
        $product2->setName('Croissant');
        $product2->setPrice('2.00');

        $item1 = new OrderItem();
        $item1->setProduct($product1);
        $item1->setQuantity(2);

        $item2 = new OrderItem();
        $item2->setProduct($product2);
        $item2->setQuantity(3);

        $order->addItem($item1);
        $order->addItem($item2);
        $order->calculateTotal();

        // 2 * 4.50 + 3 * 2.00 = 9.00 + 6.00 = 15.00
        $this->assertEquals('15.00', $order->getTotalAmount());
    }

    public function testCalculateTotalEmpty(): void
    {
        $order = new Order();
        $order->calculateTotal();

        $this->assertEquals('0.00', $order->getTotalAmount());
    }

    public function testSetNotes(): void
    {
        $order = new Order();

        $order->setNotes('Sans sucre, s\'il vous plaît');

        $this->assertEquals('Sans sucre, s\'il vous plaît', $order->getNotes());
    }

    public function testSetTableNumber(): void
    {
        $order = new Order();

        $order->setTableNumber('Table 5');

        $this->assertEquals('Table 5', $order->getTableNumber());
    }

    public function testTimestamps(): void
    {
        $order = new Order();
        $now = new \DateTimeImmutable();

        $order->setConfirmedAt($now);
        $order->setReadyAt($now);
        $order->setDeliveredAt($now);
        $order->setUpdatedAt($now);

        $this->assertEquals($now, $order->getConfirmedAt());
        $this->assertEquals($now, $order->getReadyAt());
        $this->assertEquals($now, $order->getDeliveredAt());
        $this->assertEquals($now, $order->getUpdatedAt());
    }

    public function testSetTotalAmount(): void
    {
        $order = new Order();

        $order->setTotalAmount('25.50');

        $this->assertEquals('25.50', $order->getTotalAmount());
    }
}
