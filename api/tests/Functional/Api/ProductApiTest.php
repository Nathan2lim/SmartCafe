<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Entity\Product;
use App\Tests\Functional\ApiTestCase;

class ProductApiTest extends ApiTestCase
{
    public function testGetProductsCollection(): void
    {
        $this->createProduct('Café Latte', '4.50', 'Boissons chaudes');
        $this->createProduct('Cappuccino', '5.00', 'Boissons chaudes');

        $response = $this->client->request('GET', '/api/products');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertCount(2, $data['hydra:member']);
    }

    public function testGetSingleProduct(): void
    {
        $product = $this->createProduct('Espresso', '3.00', 'Boissons chaudes');

        $response = $this->client->request('GET', '/api/products/' . $product->getId());

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals('Espresso', $data['name']);
        $this->assertEquals('3.00', $data['price']);
    }

    public function testCreateProductRequiresAdmin(): void
    {
        $user = $this->createUser();

        $response = $this->request('POST', '/api/products', [
            'json' => [
                'name' => 'Test Product',
                'price' => '10.00',
                'category' => 'Test',
            ],
        ], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCreateProductAsAdmin(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->request('POST', '/api/products', [
            'json' => [
                'name' => 'New Product',
                'price' => '7.50',
                'category' => 'Boissons chaudes',
                'description' => 'A delicious drink',
            ],
        ], $admin);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertEquals('New Product', $data['name']);
    }

    public function testUpdateProductAsAdmin(): void
    {
        $product = $this->createProduct('Old Name', '5.00', 'Category');
        $admin = $this->createAdminUser();

        $response = $this->request('PATCH', '/api/products/' . $product->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'name' => 'New Name',
                'price' => '6.00',
            ],
        ], $admin);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals('New Name', $data['name']);
        $this->assertEquals('6.00', $data['price']);
    }

    public function testDeleteProductAsAdmin(): void
    {
        $product = $this->createProduct('To Delete', '5.00', 'Category');
        $admin = $this->createAdminUser();

        $response = $this->request('DELETE', '/api/products/' . $product->getId(), [], $admin);

        $this->assertResponseStatusCodeSame(204);
    }

    private function createProduct(string $name, string $price, string $category): Product
    {
        $product = new Product();
        $product->setName($name);
        $product->setPrice($price);
        $product->setCategory($category);
        $product->setAvailable(true);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $product;
    }
}
