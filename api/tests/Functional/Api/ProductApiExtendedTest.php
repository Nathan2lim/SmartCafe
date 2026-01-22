<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\Product;
use App\Tests\Functional\ApiTestCase;

class ProductApiExtendedTest extends ApiTestCase
{
    public function testGetProductsPublic(): void
    {
        $product = new Product();
        $product->setName('Test Café');
        $product->setPrice('4.50');
        $product->setCategory('Boissons');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $response = $this->client->request('GET', '/api/products', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        // Check for either JSON-LD (hydra:member) or JSON array format
        $members = $data['hydra:member'] ?? $data['member'] ?? $data;
        $this->assertIsArray($members);
        $this->assertGreaterThanOrEqual(1, \count($members));
    }

    public function testGetProductByIdPublic(): void
    {
        $product = new Product();
        $product->setName('Espresso');
        $product->setPrice('2.50');
        $product->setCategory('Boissons chaudes');
        $product->setDescription('Café fort et concentré');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $response = $this->client->request('GET', '/api/products/' . $product->getId());

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertEquals('Espresso', $data['name']);
        $this->assertEquals('2.50', $data['price']);
        $this->assertEquals('Boissons chaudes', $data['category']);
    }

    public function testCreateProductAsAdmin(): void
    {
        $admin = $this->createAdminUser('productadmin@test.fr', 'admin123');

        $response = $this->request('POST', '/api/products', [
            'json' => [
                'name' => 'Nouveau Café',
                'description' => 'Un délicieux café',
                'price' => '5.00',
                'category' => 'Boissons chaudes',
                'available' => true,
                'stockQuantity' => 100,
                'lowStockThreshold' => 15,
            ],
        ], $admin);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();

        $this->assertEquals('Nouveau Café', $data['name']);
        $this->assertEquals('5.00', $data['price']);
        $this->assertEquals(100, $data['stockQuantity']);
    }

    public function testCreateProductAsUserForbidden(): void
    {
        $user = $this->createUser('productforbidden@test.fr', 'password123');

        $this->request('POST', '/api/products', [
            'json' => [
                'name' => 'Forbidden Product',
                'price' => '5.00',
                'category' => 'Boissons',
            ],
        ], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateProductUnauthenticated(): void
    {
        $this->client->request('POST', '/api/products', [
            'json' => [
                'name' => 'Unauthenticated Product',
                'price' => '5.00',
                'category' => 'Boissons',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateProductAsAdmin(): void
    {
        $admin = $this->createAdminUser('updateproductadmin@test.fr', 'admin123');

        $product = new Product();
        $product->setName('Original Product');
        $product->setPrice('4.00');
        $product->setCategory('Boissons');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $response = $this->request('PATCH', '/api/products/' . $product->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Updated Product',
                'price' => '5.50',
            ],
        ], $admin);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertEquals('Updated Product', $data['name']);
        $this->assertEquals('5.50', $data['price']);
    }

    public function testUpdateProductAsUserForbidden(): void
    {
        $user = $this->createUser('updateproductforbidden@test.fr', 'password123');

        $product = new Product();
        $product->setName('Protected Product');
        $product->setPrice('4.00');
        $product->setCategory('Boissons');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->request('PATCH', '/api/products/' . $product->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Hacked Product',
            ],
        ], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteProductAsAdmin(): void
    {
        $admin = $this->createAdminUser('deleteproductadmin@test.fr', 'admin123');

        $product = new Product();
        $product->setName('To Delete');
        $product->setPrice('4.00');
        $product->setCategory('Boissons');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->request('DELETE', '/api/products/' . $product->getId(), [], $admin);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteProductAsUserForbidden(): void
    {
        $user = $this->createUser('deleteproductforbidden@test.fr', 'password123');

        $product = new Product();
        $product->setName('Protected Delete');
        $product->setPrice('4.00');
        $product->setCategory('Boissons');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->request('DELETE', '/api/products/' . $product->getId(), [], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetLowStockProductsAsAdmin(): void
    {
        $admin = $this->createAdminUser('lowstockproductadmin@test.fr', 'admin123');

        // Create low stock product
        $lowStockProduct = new Product();
        $lowStockProduct->setName('Low Stock Product');
        $lowStockProduct->setPrice('4.00');
        $lowStockProduct->setCategory('Boissons');
        $lowStockProduct->setStockQuantity(5);
        $lowStockProduct->setLowStockThreshold(10);
        $this->entityManager->persist($lowStockProduct);

        // Create normal stock product
        $normalProduct = new Product();
        $normalProduct->setName('Normal Stock Product');
        $normalProduct->setPrice('4.00');
        $normalProduct->setCategory('Boissons');
        $normalProduct->setStockQuantity(100);
        $normalProduct->setLowStockThreshold(10);
        $this->entityManager->persist($normalProduct);

        $this->entityManager->flush();

        $response = $this->request('GET', '/api/products/low-stock', [], $admin);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        // Check for either JSON-LD (hydra:member) or JSON array format
        $members = $data['hydra:member'] ?? $data['member'] ?? $data;
        $this->assertIsArray($members);

        $lowStockNames = array_map(fn ($p) => $p['name'], $members);
        $this->assertContains('Low Stock Product', $lowStockNames);
    }

    public function testGetLowStockProductsAsUserForbidden(): void
    {
        $user = $this->createUser('lowstockproductforbidden@test.fr', 'password123');

        $this->request('GET', '/api/products/low-stock', [], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetLowStockProductsUnauthenticated(): void
    {
        $this->client->request('GET', '/api/products/low-stock');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateProductValidationMissingName(): void
    {
        $admin = $this->createAdminUser('validationproductadmin@test.fr', 'admin123');

        $response = $this->request('POST', '/api/products', [
            'json' => [
                'price' => '4.00',
                'category' => 'Boissons',
            ],
        ], $admin);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateProductValidationMissingPrice(): void
    {
        $admin = $this->createAdminUser('pricevalidationproductadmin@test.fr', 'admin123');

        $response = $this->request('POST', '/api/products', [
            'json' => [
                'name' => 'No Price Product',
                'category' => 'Boissons',
            ],
        ], $admin);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateProductValidationMissingCategory(): void
    {
        $admin = $this->createAdminUser('categoryvalidationproductadmin@test.fr', 'admin123');

        $response = $this->request('POST', '/api/products', [
            'json' => [
                'name' => 'No Category Product',
                'price' => '4.00',
            ],
        ], $admin);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testProductIsLowStock(): void
    {
        $product = new Product();
        $product->setName('Check Low Stock');
        $product->setPrice('4.00');
        $product->setCategory('Boissons');
        $product->setStockQuantity(5);
        $product->setLowStockThreshold(10);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $response = $this->client->request('GET', '/api/products/' . $product->getId());

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertTrue($data['lowStock']);
    }

    public function testProductIsNotLowStock(): void
    {
        $product = new Product();
        $product->setName('Not Low Stock');
        $product->setPrice('4.00');
        $product->setCategory('Boissons');
        $product->setStockQuantity(100);
        $product->setLowStockThreshold(10);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $response = $this->client->request('GET', '/api/products/' . $product->getId());

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertFalse($data['lowStock']);
    }

    public function testProductWithNullStockNotLowStock(): void
    {
        $product = new Product();
        $product->setName('Unlimited Stock');
        $product->setPrice('4.00');
        $product->setCategory('Boissons');
        $product->setStockQuantity(null);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $response = $this->client->request('GET', '/api/products/' . $product->getId());

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertFalse($data['lowStock']);
    }

    public function testSetProductAvailability(): void
    {
        $admin = $this->createAdminUser('availabilityadmin@test.fr', 'admin123');

        $product = new Product();
        $product->setName('Toggle Availability');
        $product->setPrice('4.00');
        $product->setCategory('Boissons');
        $product->setAvailable(true);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $response = $this->request('PATCH', '/api/products/' . $product->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'available' => false,
            ],
        ], $admin);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertFalse($data['available']);
    }

    public function testSetProductAlaCarte(): void
    {
        $admin = $this->createAdminUser('alacarteadmin@test.fr', 'admin123');

        $product = new Product();
        $product->setName('A La Carte Product');
        $product->setPrice('4.00');
        $product->setCategory('Boissons');
        $product->setAlaCarte(false);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $response = $this->request('PATCH', '/api/products/' . $product->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'alaCarte' => true,
            ],
        ], $admin);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertTrue($data['alaCarte']);
    }
}
