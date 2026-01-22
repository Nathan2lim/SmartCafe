<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\Extra;
use App\Entity\Product;
use App\Entity\ProductExtra;
use App\Tests\Functional\ApiTestCase;

class OrderApiTest extends ApiTestCase
{
    private Product $product;
    private Extra $extra;
    private ProductExtra $productExtra;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test product
        $this->product = new Product();
        $this->product->setName('Café Latte');
        $this->product->setPrice('4.50');
        $this->product->setCategory('Boissons');
        $this->product->setAvailable(true);
        $this->product->setStockQuantity(100);
        $this->entityManager->persist($this->product);

        // Create test extra
        $this->extra = new Extra();
        $this->extra->setName('Chantilly');
        $this->extra->setPrice('0.50');
        $this->extra->setStockQuantity(50);
        $this->extra->setAvailable(true);
        $this->entityManager->persist($this->extra);

        // Create product-extra association
        $this->productExtra = new ProductExtra();
        $this->productExtra->setProduct($this->product);
        $this->productExtra->setExtra($this->extra);
        $this->productExtra->setMaxQuantity(3);
        $this->entityManager->persist($this->productExtra);

        $this->entityManager->flush();
    }

    public function testCreateOrderUnauthenticated(): void
    {
        $this->client->request('POST', '/api/orders', [
            'json' => [
                'items' => [
                    ['product' => '/api/products/' . $this->product->getId(), 'quantity' => 1],
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateOrderSuccess(): void
    {
        $user = $this->createUser('order@test.fr', 'password123');

        $response = $this->request('POST', '/api/orders', [
            'json' => [
                'items' => [
                    ['product' => '/api/products/' . $this->product->getId(), 'quantity' => 2],
                ],
                'notes' => 'Sans sucre',
                'tableNumber' => 'Table 5',
            ],
        ], $user);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();

        $this->assertArrayHasKey('orderNumber', $data);
        $this->assertStringStartsWith('ORD-', $data['orderNumber']);
        $this->assertEquals('pending', $data['status']);
        $this->assertEquals('9.00', $data['totalAmount']);
        $this->assertEquals('Sans sucre', $data['notes']);
        $this->assertEquals('Table 5', $data['tableNumber']);
        $this->assertCount(1, $data['items']);
    }

    public function testCreateOrderWithSpecialInstructions(): void
    {
        $user = $this->createUser('orderspecial@test.fr', 'password123');

        $response = $this->request('POST', '/api/orders', [
            'json' => [
                'items' => [
                    [
                        'product' => '/api/products/' . $this->product->getId(),
                        'quantity' => 2,
                        'specialInstructions' => 'Extra hot please',
                    ],
                ],
            ],
        ], $user);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();

        // 2 * 4.50 = 9.00
        $this->assertEquals('9.00', $data['totalAmount']);
        $this->assertEquals('Extra hot please', $data['items'][0]['specialInstructions']);
    }

    public function testCreateOrderWithInvalidProduct(): void
    {
        $user = $this->createUser('invalid@test.fr', 'password123');

        $response = $this->request('POST', '/api/orders', [
            'json' => [
                'items' => [
                    ['product' => '/api/products/99999', 'quantity' => 1],
                ],
            ],
        ], $user);

        // API Platform returns 400 for invalid IRI reference
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateOrderWithUnavailableProduct(): void
    {
        $unavailableProduct = new Product();
        $unavailableProduct->setName('Unavailable');
        $unavailableProduct->setPrice('5.00');
        $unavailableProduct->setCategory('Boissons');
        $unavailableProduct->setAvailable(false);
        $this->entityManager->persist($unavailableProduct);
        $this->entityManager->flush();

        $user = $this->createUser('unavailable@test.fr', 'password123');

        $response = $this->request('POST', '/api/orders', [
            'json' => [
                'items' => [
                    ['product' => '/api/products/' . $unavailableProduct->getId(), 'quantity' => 1],
                ],
            ],
        ], $user);

        // Unavailable product returns 400 error
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCreateOrderWithInsufficientStock(): void
    {
        $lowStockProduct = new Product();
        $lowStockProduct->setName('Low Stock');
        $lowStockProduct->setPrice('5.00');
        $lowStockProduct->setCategory('Boissons');
        $lowStockProduct->setAvailable(true);
        $lowStockProduct->setStockQuantity(2);
        $this->entityManager->persist($lowStockProduct);
        $this->entityManager->flush();

        $user = $this->createUser('lowstock@test.fr', 'password123');

        $response = $this->request('POST', '/api/orders', [
            'json' => [
                'items' => [
                    ['product' => '/api/products/' . $lowStockProduct->getId(), 'quantity' => 10],
                ],
            ],
        ], $user);

        // Insufficient stock returns 400 error
        $this->assertResponseStatusCodeSame(400);
    }

    public function testGetMyOrders(): void
    {
        $user = $this->createUser('myorders@test.fr', 'password123');

        // Create an order first
        $this->request('POST', '/api/orders', [
            'json' => [
                'items' => [
                    ['product' => '/api/products/' . $this->product->getId(), 'quantity' => 1],
                ],
            ],
        ], $user);

        $response = $this->request('GET', '/api/auth/me/orders', [], $user);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        // Check for either JSON-LD (hydra:member) or JSON array format
        $members = $data['hydra:member'] ?? $data['member'] ?? $data;
        $this->assertIsArray($members);
        $this->assertCount(1, $members);
    }

    public function testGetMyOrdersUnauthenticated(): void
    {
        $this->client->request('GET', '/api/auth/me/orders');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetAllOrdersAsAdmin(): void
    {
        $admin = $this->createAdminUser('orderadmin@test.fr', 'admin123');
        $user = $this->createUser('regularuser@test.fr', 'password123');

        // Create order as regular user
        $this->request('POST', '/api/orders', [
            'json' => [
                'items' => [
                    ['product' => '/api/products/' . $this->product->getId(), 'quantity' => 1],
                ],
            ],
        ], $user);

        // Admin gets all orders
        $response = $this->request('GET', '/api/orders', [], $admin);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        // Check for either JSON-LD (hydra:member) or JSON array format
        $members = $data['hydra:member'] ?? $data['member'] ?? $data;
        $this->assertIsArray($members);
        $this->assertGreaterThanOrEqual(1, \count($members));
    }

    public function testGetAllOrdersAsUserForbidden(): void
    {
        $user = $this->createUser('forbidden@test.fr', 'password123');

        $this->request('GET', '/api/orders', [], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetOrderAsOwner(): void
    {
        $user = $this->createUser('owner@test.fr', 'password123');

        // Create an order
        $createResponse = $this->request('POST', '/api/orders', [
            'json' => [
                'items' => [
                    ['product' => '/api/products/' . $this->product->getId(), 'quantity' => 1],
                ],
            ],
        ], $user);

        $orderId = $createResponse->toArray()['id'];

        // Get the order
        $response = $this->request('GET', '/api/orders/' . $orderId, [], $user);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals($orderId, $data['id']);
    }

    public function testGetOrderAsOtherUserForbidden(): void
    {
        $user1 = $this->createUser('user1@test.fr', 'password123');
        $user2 = $this->createUser('user2@test.fr', 'password123');

        // Create order as user1
        $createResponse = $this->request('POST', '/api/orders', [
            'json' => [
                'items' => [
                    ['product' => '/api/products/' . $this->product->getId(), 'quantity' => 1],
                ],
            ],
        ], $user1);

        $orderId = $createResponse->toArray()['id'];

        // Try to get it as user2
        $this->request('GET', '/api/orders/' . $orderId, [], $user2);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateOrderStatusAsAdmin(): void
    {
        $admin = $this->createAdminUser('statusadmin@test.fr', 'admin123');
        $user = $this->createUser('statususer@test.fr', 'password123');

        // Create order
        $createResponse = $this->request('POST', '/api/orders', [
            'json' => [
                'items' => [
                    ['product' => '/api/products/' . $this->product->getId(), 'quantity' => 1],
                ],
            ],
        ], $user);

        $orderId = $createResponse->toArray()['id'];

        // Update status to confirmed
        $response = $this->request('PATCH', '/api/orders/' . $orderId, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'status' => 'confirmed',
            ],
        ], $admin);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals('confirmed', $data['status']);
    }

    public function testUpdateOrderStatusAsUserForbidden(): void
    {
        $user = $this->createUser('statusforbidden@test.fr', 'password123');

        // Create order
        $createResponse = $this->request('POST', '/api/orders', [
            'json' => [
                'items' => [
                    ['product' => '/api/products/' . $this->product->getId(), 'quantity' => 1],
                ],
            ],
        ], $user);

        $orderId = $createResponse->toArray()['id'];

        // Try to update status as regular user
        $this->request('PATCH', '/api/orders/' . $orderId, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'status' => 'confirmed',
            ],
        ], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteOrderAsAdmin(): void
    {
        $admin = $this->createAdminUser('deleteadmin@test.fr', 'admin123');
        $user = $this->createUser('deleteuser@test.fr', 'password123');

        // Create order
        $createResponse = $this->request('POST', '/api/orders', [
            'json' => [
                'items' => [
                    ['product' => '/api/products/' . $this->product->getId(), 'quantity' => 1],
                ],
            ],
        ], $user);

        $orderId = $createResponse->toArray()['id'];

        // Delete as admin
        $this->request('DELETE', '/api/orders/' . $orderId, [], $admin);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteOrderAsUserForbidden(): void
    {
        $user = $this->createUser('deleteforbidden@test.fr', 'password123');

        // Create order
        $createResponse = $this->request('POST', '/api/orders', [
            'json' => [
                'items' => [
                    ['product' => '/api/products/' . $this->product->getId(), 'quantity' => 1],
                ],
            ],
        ], $user);

        $orderId = $createResponse->toArray()['id'];

        // Try to delete as regular user
        $this->request('DELETE', '/api/orders/' . $orderId, [], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateOrderWithEmptyItems(): void
    {
        $user = $this->createUser('emptyitems@test.fr', 'password123');

        $response = $this->request('POST', '/api/orders', [
            'json' => [
                'items' => [],
            ],
        ], $user);

        $this->assertResponseStatusCodeSame(422);
    }
}
