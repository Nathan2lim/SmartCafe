<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function testAllStatusValues(): void
    {
        $this->assertEquals('pending', OrderStatus::PENDING->value);
        $this->assertEquals('confirmed', OrderStatus::CONFIRMED->value);
        $this->assertEquals('preparing', OrderStatus::PREPARING->value);
        $this->assertEquals('ready', OrderStatus::READY->value);
        $this->assertEquals('delivered', OrderStatus::DELIVERED->value);
        $this->assertEquals('cancelled', OrderStatus::CANCELLED->value);
    }

    public function testAllStatusLabels(): void
    {
        $this->assertEquals('En attente', OrderStatus::PENDING->label());
        $this->assertEquals('Confirmée', OrderStatus::CONFIRMED->label());
        $this->assertEquals('En préparation', OrderStatus::PREPARING->label());
        $this->assertEquals('Prête', OrderStatus::READY->label());
        $this->assertEquals('Livrée', OrderStatus::DELIVERED->label());
        $this->assertEquals('Annulée', OrderStatus::CANCELLED->label());
    }

    // Tests for PENDING status
    public function testPendingCanTransitionToConfirmed(): void
    {
        $this->assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::CONFIRMED));
    }

    public function testPendingCanTransitionToCancelled(): void
    {
        $this->assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::CANCELLED));
    }

    public function testPendingCannotTransitionToPreparing(): void
    {
        $this->assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::PREPARING));
    }

    public function testPendingCannotTransitionToReady(): void
    {
        $this->assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::READY));
    }

    public function testPendingCannotTransitionToDelivered(): void
    {
        $this->assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::DELIVERED));
    }

    // Tests for CONFIRMED status
    public function testConfirmedCanTransitionToPreparing(): void
    {
        $this->assertTrue(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::PREPARING));
    }

    public function testConfirmedCanTransitionToCancelled(): void
    {
        $this->assertTrue(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::CANCELLED));
    }

    public function testConfirmedCannotTransitionToPending(): void
    {
        $this->assertFalse(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::PENDING));
    }

    public function testConfirmedCannotTransitionToReady(): void
    {
        $this->assertFalse(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::READY));
    }

    // Tests for PREPARING status
    public function testPreparingCanTransitionToReady(): void
    {
        $this->assertTrue(OrderStatus::PREPARING->canTransitionTo(OrderStatus::READY));
    }

    public function testPreparingCanTransitionToCancelled(): void
    {
        $this->assertTrue(OrderStatus::PREPARING->canTransitionTo(OrderStatus::CANCELLED));
    }

    public function testPreparingCannotTransitionToDelivered(): void
    {
        $this->assertFalse(OrderStatus::PREPARING->canTransitionTo(OrderStatus::DELIVERED));
    }

    // Tests for READY status
    public function testReadyCanTransitionToDelivered(): void
    {
        $this->assertTrue(OrderStatus::READY->canTransitionTo(OrderStatus::DELIVERED));
    }

    public function testReadyCannotTransitionToCancelled(): void
    {
        $this->assertFalse(OrderStatus::READY->canTransitionTo(OrderStatus::CANCELLED));
    }

    public function testReadyCannotTransitionToPreparing(): void
    {
        $this->assertFalse(OrderStatus::READY->canTransitionTo(OrderStatus::PREPARING));
    }

    // Tests for DELIVERED status (terminal)
    public function testDeliveredCannotTransitionToAnyStatus(): void
    {
        $this->assertFalse(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::PENDING));
        $this->assertFalse(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::CONFIRMED));
        $this->assertFalse(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::PREPARING));
        $this->assertFalse(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::READY));
        $this->assertFalse(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::CANCELLED));
    }

    // Tests for CANCELLED status (terminal)
    public function testCancelledCannotTransitionToAnyStatus(): void
    {
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::PENDING));
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::CONFIRMED));
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::PREPARING));
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::READY));
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::DELIVERED));
    }

    // Tests for nextPossibleStatuses
    public function testPendingNextPossibleStatuses(): void
    {
        $expected = [OrderStatus::CONFIRMED, OrderStatus::CANCELLED];
        $this->assertEquals($expected, OrderStatus::PENDING->nextPossibleStatuses());
    }

    public function testConfirmedNextPossibleStatuses(): void
    {
        $expected = [OrderStatus::PREPARING, OrderStatus::CANCELLED];
        $this->assertEquals($expected, OrderStatus::CONFIRMED->nextPossibleStatuses());
    }

    public function testPreparingNextPossibleStatuses(): void
    {
        $expected = [OrderStatus::READY, OrderStatus::CANCELLED];
        $this->assertEquals($expected, OrderStatus::PREPARING->nextPossibleStatuses());
    }

    public function testReadyNextPossibleStatuses(): void
    {
        $expected = [OrderStatus::DELIVERED];
        $this->assertEquals($expected, OrderStatus::READY->nextPossibleStatuses());
    }

    public function testDeliveredNextPossibleStatuses(): void
    {
        $this->assertEmpty(OrderStatus::DELIVERED->nextPossibleStatuses());
    }

    public function testCancelledNextPossibleStatuses(): void
    {
        $this->assertEmpty(OrderStatus::CANCELLED->nextPossibleStatuses());
    }

    // Edge case tests
    public function testCannotTransitionToSameStatus(): void
    {
        $this->assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::PENDING));
        $this->assertFalse(OrderStatus::CONFIRMED->canTransitionTo(OrderStatus::CONFIRMED));
        $this->assertFalse(OrderStatus::PREPARING->canTransitionTo(OrderStatus::PREPARING));
        $this->assertFalse(OrderStatus::READY->canTransitionTo(OrderStatus::READY));
    }

    public function testStatusCount(): void
    {
        $this->assertCount(6, OrderStatus::cases());
    }
}
