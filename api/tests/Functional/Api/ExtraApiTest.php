<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\Extra;
use App\Tests\Functional\ApiTestCase;

class ExtraApiTest extends ApiTestCase
{
    public function testGetExtrasPublic(): void
    {
        // Create some extras
        $extra = new Extra();
        $extra->setName('Chantilly');
        $extra->setPrice('0.50');
        $extra->setStockQuantity(100);
        $this->entityManager->persist($extra);
        $this->entityManager->flush();

        $response = $this->client->request('GET', '/api/extras', [
            'headers' => ['Accept' => 'application/ld+json'],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        // Check for either JSON-LD (hydra:member) or JSON array format
        $members = $data['hydra:member'] ?? $data['member'] ?? $data;
        $this->assertIsArray($members);
        $this->assertGreaterThanOrEqual(1, \count($members));
    }

    public function testGetExtraByIdPublic(): void
    {
        $extra = new Extra();
        $extra->setName('Sirop Caramel');
        $extra->setPrice('0.60');
        $extra->setStockQuantity(50);
        $this->entityManager->persist($extra);
        $this->entityManager->flush();

        $response = $this->client->request('GET', '/api/extras/' . $extra->getId());

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertEquals('Sirop Caramel', $data['name']);
        $this->assertEquals('0.60', $data['price']);
    }

    public function testCreateExtraAsAdmin(): void
    {
        $admin = $this->createAdminUser('extraadmin@test.fr', 'admin123');

        $response = $this->request('POST', '/api/extras', [
            'json' => [
                'name' => 'Lait d\'Avoine',
                'description' => 'Alternative végétale',
                'price' => '0.70',
                'stockQuantity' => 80,
                'lowStockThreshold' => 15,
            ],
        ], $admin);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();

        $this->assertEquals('Lait d\'Avoine', $data['name']);
        $this->assertEquals('0.70', $data['price']);
        $this->assertEquals(80, $data['stockQuantity']);
    }

    public function testCreateExtraAsUserForbidden(): void
    {
        $user = $this->createUser('extraforbidden@test.fr', 'password123');

        $this->request('POST', '/api/extras', [
            'json' => [
                'name' => 'Forbidden Extra',
                'price' => '0.50',
                'stockQuantity' => 50,
            ],
        ], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateExtraUnauthenticated(): void
    {
        $this->client->request('POST', '/api/extras', [
            'json' => [
                'name' => 'Unauthenticated Extra',
                'price' => '0.50',
                'stockQuantity' => 50,
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateExtraAsAdmin(): void
    {
        $admin = $this->createAdminUser('updateextraadmin@test.fr', 'admin123');

        $extra = new Extra();
        $extra->setName('Original Name');
        $extra->setPrice('0.50');
        $extra->setStockQuantity(50);
        $this->entityManager->persist($extra);
        $this->entityManager->flush();

        $response = $this->request('PATCH', '/api/extras/' . $extra->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Updated Name',
                'price' => '0.75',
            ],
        ], $admin);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertEquals('Updated Name', $data['name']);
        $this->assertEquals('0.75', $data['price']);
    }

    public function testUpdateExtraAsUserForbidden(): void
    {
        $user = $this->createUser('updateextraforbidden@test.fr', 'password123');

        $extra = new Extra();
        $extra->setName('Protected Extra');
        $extra->setPrice('0.50');
        $extra->setStockQuantity(50);
        $this->entityManager->persist($extra);
        $this->entityManager->flush();

        $this->request('PATCH', '/api/extras/' . $extra->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'Hacked Name',
            ],
        ], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteExtraAsAdmin(): void
    {
        $admin = $this->createAdminUser('deleteextraadmin@test.fr', 'admin123');

        $extra = new Extra();
        $extra->setName('To Delete');
        $extra->setPrice('0.50');
        $extra->setStockQuantity(50);
        $this->entityManager->persist($extra);
        $this->entityManager->flush();

        $this->request('DELETE', '/api/extras/' . $extra->getId(), [], $admin);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteExtraAsUserForbidden(): void
    {
        $user = $this->createUser('deleteextraforbidden@test.fr', 'password123');

        $extra = new Extra();
        $extra->setName('Protected Delete');
        $extra->setPrice('0.50');
        $extra->setStockQuantity(50);
        $this->entityManager->persist($extra);
        $this->entityManager->flush();

        $this->request('DELETE', '/api/extras/' . $extra->getId(), [], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetLowStockExtrasAsAdmin(): void
    {
        $admin = $this->createAdminUser('lowstockadmin@test.fr', 'admin123');

        // Create a low stock extra
        $lowStockExtra = new Extra();
        $lowStockExtra->setName('Low Stock Extra');
        $lowStockExtra->setPrice('0.50');
        $lowStockExtra->setStockQuantity(5);
        $lowStockExtra->setLowStockThreshold(10);
        $this->entityManager->persist($lowStockExtra);

        // Create a normal stock extra
        $normalExtra = new Extra();
        $normalExtra->setName('Normal Stock Extra');
        $normalExtra->setPrice('0.50');
        $normalExtra->setStockQuantity(100);
        $normalExtra->setLowStockThreshold(10);
        $this->entityManager->persist($normalExtra);

        $this->entityManager->flush();

        $response = $this->request('GET', '/api/extras/low-stock', [], $admin);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        // Check for either JSON-LD (hydra:member) or JSON array format
        $members = $data['hydra:member'] ?? $data['member'] ?? $data;
        $this->assertIsArray($members);

        // Check that low stock extra is in the list
        $lowStockNames = array_map(fn ($e) => $e['name'], $members);
        $this->assertContains('Low Stock Extra', $lowStockNames);
    }

    public function testGetLowStockExtrasAsUserForbidden(): void
    {
        $user = $this->createUser('lowstockforbidden@test.fr', 'password123');

        $this->request('GET', '/api/extras/low-stock', [], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetLowStockExtrasUnauthenticated(): void
    {
        $this->client->request('GET', '/api/extras/low-stock');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testRestockExtraAsAdmin(): void
    {
        $admin = $this->createAdminUser('restockadmin@test.fr', 'admin123');

        $extra = new Extra();
        $extra->setName('To Restock');
        $extra->setPrice('0.50');
        $extra->setStockQuantity(10);
        $this->entityManager->persist($extra);
        $this->entityManager->flush();

        $response = $this->request('POST', '/api/extras/' . $extra->getId() . '/restock', [
            'json' => [
                'quantity' => 50,
            ],
        ], $admin);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertEquals(60, $data['stockQuantity']); // 10 + 50
    }

    public function testRestockExtraAsUserForbidden(): void
    {
        $user = $this->createUser('restockforbidden@test.fr', 'password123');

        $extra = new Extra();
        $extra->setName('Protected Restock');
        $extra->setPrice('0.50');
        $extra->setStockQuantity(10);
        $this->entityManager->persist($extra);
        $this->entityManager->flush();

        $this->request('POST', '/api/extras/' . $extra->getId() . '/restock', [
            'json' => [
                'quantity' => 50,
            ],
        ], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateExtraValidationMissingName(): void
    {
        $admin = $this->createAdminUser('validationadmin@test.fr', 'admin123');

        $response = $this->request('POST', '/api/extras', [
            'json' => [
                'price' => '0.50',
                'stockQuantity' => 50,
            ],
        ], $admin);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testCreateExtraValidationMissingPrice(): void
    {
        $admin = $this->createAdminUser('pricevalidationadmin@test.fr', 'admin123');

        $response = $this->request('POST', '/api/extras', [
            'json' => [
                'name' => 'No Price Extra',
                'stockQuantity' => 50,
            ],
        ], $admin);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testExtraIsLowStock(): void
    {
        $admin = $this->createAdminUser('islowstockadmin@test.fr', 'admin123');

        $extra = new Extra();
        $extra->setName('Check Low Stock');
        $extra->setPrice('0.50');
        $extra->setStockQuantity(5);
        $extra->setLowStockThreshold(10);
        $this->entityManager->persist($extra);
        $this->entityManager->flush();

        $response = $this->request('GET', '/api/extras/' . $extra->getId(), [], $admin);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertTrue($data['lowStock']);
    }

    public function testExtraIsNotLowStock(): void
    {
        $extra = new Extra();
        $extra->setName('Check Not Low Stock');
        $extra->setPrice('0.50');
        $extra->setStockQuantity(100);
        $extra->setLowStockThreshold(10);
        $this->entityManager->persist($extra);
        $this->entityManager->flush();

        $response = $this->client->request('GET', '/api/extras/' . $extra->getId());

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertFalse($data['lowStock']);
    }
}
