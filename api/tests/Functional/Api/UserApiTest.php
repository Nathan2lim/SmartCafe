<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Functional\ApiTestCase;

class UserApiTest extends ApiTestCase
{
    public function testRegisterUser(): void
    {
        $response = $this->client->request('POST', '/api/users', [
            'json' => [
                'email' => 'newuser@smartcafe.fr',
                'plainPassword' => 'securePassword123',
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();

        $this->assertEquals('newuser@smartcafe.fr', $data['email']);
        $this->assertEquals('John', $data['firstName']);
        $this->assertEquals('Doe', $data['lastName']);
        $this->assertContains('ROLE_USER', $data['roles']);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('plainPassword', $data);
    }

    public function testRegisterUserWithPhone(): void
    {
        $response = $this->client->request('POST', '/api/users', [
            'json' => [
                'email' => 'userwithphone@smartcafe.fr',
                'plainPassword' => 'securePassword123',
                'firstName' => 'Jane',
                'lastName' => 'Doe',
                'phone' => '+33612345678',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();

        $this->assertEquals('+33612345678', $data['phone']);
    }

    public function testRegisterUserValidationMissingEmail(): void
    {
        $response = $this->client->request('POST', '/api/users', [
            'json' => [
                'plainPassword' => 'securePassword123',
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterUserValidationInvalidEmail(): void
    {
        $response = $this->client->request('POST', '/api/users', [
            'json' => [
                'email' => 'invalid-email',
                'plainPassword' => 'securePassword123',
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterUserValidationMissingFirstName(): void
    {
        $response = $this->client->request('POST', '/api/users', [
            'json' => [
                'email' => 'missingname@smartcafe.fr',
                'plainPassword' => 'securePassword123',
                'lastName' => 'Doe',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterUserValidationMissingLastName(): void
    {
        $response = $this->client->request('POST', '/api/users', [
            'json' => [
                'email' => 'missinglastname@smartcafe.fr',
                'plainPassword' => 'securePassword123',
                'firstName' => 'John',
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testRegisterUserDuplicateEmail(): void
    {
        // Create first user
        $this->client->request('POST', '/api/users', [
            'json' => [
                'email' => 'duplicate@smartcafe.fr',
                'plainPassword' => 'securePassword123',
                'firstName' => 'John',
                'lastName' => 'Doe',
            ],
        ]);

        // Try to create second user with same email
        $response = $this->client->request('POST', '/api/users', [
            'json' => [
                'email' => 'duplicate@smartcafe.fr',
                'plainPassword' => 'anotherPassword123',
                'firstName' => 'Jane',
                'lastName' => 'Smith',
            ],
        ]);

        $this->assertResponseStatusCodeSame(409); // ConflictHttpException for duplicate email
    }

    public function testGetMyProfile(): void
    {
        $user = $this->createUser('profile@test.fr', 'password123');

        $response = $this->request('GET', '/api/auth/me', [], $user);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertEquals('profile@test.fr', $data['email']);
        $this->assertArrayHasKey('ordersUrl', $data);
        $this->assertArrayHasKey('loyaltyUrl', $data);
    }

    public function testGetMyProfileUnauthenticated(): void
    {
        $this->client->request('GET', '/api/auth/me');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetUserAsOwner(): void
    {
        $user = $this->createUser('owner@test.fr', 'password123');

        $response = $this->request('GET', '/api/users/' . $user->getId(), [], $user);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals('owner@test.fr', $data['email']);
    }

    public function testGetUserAsOtherUserForbidden(): void
    {
        $user1 = $this->createUser('user1@test.fr', 'password123');
        $user2 = $this->createUser('user2@test.fr', 'password123');

        $this->request('GET', '/api/users/' . $user1->getId(), [], $user2);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetUserAsAdmin(): void
    {
        $admin = $this->createAdminUser('admin@test.fr', 'admin123');
        $user = $this->createUser('regular@test.fr', 'password123');

        $response = $this->request('GET', '/api/users/' . $user->getId(), [], $admin);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals('regular@test.fr', $data['email']);
    }

    public function testGetAllUsersAsAdmin(): void
    {
        $admin = $this->createAdminUser('allusersadmin@test.fr', 'admin123');
        $this->createUser('user1@test.fr', 'password123');
        $this->createUser('user2@test.fr', 'password123');

        $response = $this->request('GET', '/api/users', [
            'headers' => ['Accept' => 'application/ld+json'],
        ], $admin);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        // Check for either JSON-LD (hydra:member) or JSON array format
        $members = $data['hydra:member'] ?? $data['member'] ?? $data;
        $this->assertIsArray($members);
        $this->assertGreaterThanOrEqual(3, \count($members));
    }

    public function testGetAllUsersAsUserForbidden(): void
    {
        $user = $this->createUser('forbidden@test.fr', 'password123');

        $this->request('GET', '/api/users', [], $user);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateMyProfile(): void
    {
        $user = $this->createUser('update@test.fr', 'password123');

        $response = $this->request('PATCH', '/api/users/' . $user->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'firstName' => 'UpdatedFirstName',
                'lastName' => 'UpdatedLastName',
                'phone' => '+33698765432',
            ],
        ], $user);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertEquals('UpdatedFirstName', $data['firstName']);
        $this->assertEquals('UpdatedLastName', $data['lastName']);
        $this->assertEquals('+33698765432', $data['phone']);
    }

    public function testUpdateOtherUserAsForbidden(): void
    {
        $user1 = $this->createUser('target@test.fr', 'password123');
        $user2 = $this->createUser('attacker@test.fr', 'password123');

        $this->request('PATCH', '/api/users/' . $user1->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'firstName' => 'Hacked',
            ],
        ], $user2);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUpdateUserAsAdmin(): void
    {
        $admin = $this->createAdminUser('updateadmin@test.fr', 'admin123');
        $user = $this->createUser('toupdate@test.fr', 'password123');

        $response = $this->request('PATCH', '/api/users/' . $user->getId(), [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json' => [
                'firstName' => 'AdminUpdated',
            ],
        ], $admin);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertEquals('AdminUpdated', $data['firstName']);
    }

    public function testDeleteUserAsAdmin(): void
    {
        $admin = $this->createAdminUser('deleteadmin@test.fr', 'admin123');
        $user = $this->createUser('todelete@test.fr', 'password123');

        $this->request('DELETE', '/api/users/' . $user->getId(), [], $admin);

        $this->assertResponseStatusCodeSame(204);
    }

    public function testDeleteUserAsUserForbidden(): void
    {
        $user1 = $this->createUser('target2@test.fr', 'password123');
        $user2 = $this->createUser('attacker2@test.fr', 'password123');

        $this->request('DELETE', '/api/users/' . $user1->getId(), [], $user2);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteSelfAsUserForbidden(): void
    {
        $user = $this->createUser('selfdelete@test.fr', 'password123');

        $this->request('DELETE', '/api/users/' . $user->getId(), [], $user);

        $this->assertResponseStatusCodeSame(403);
    }
}
