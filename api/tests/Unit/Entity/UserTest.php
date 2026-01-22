<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\LoyaltyAccount;
use App\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User();

        $this->assertNull($user->getId());
        $this->assertContains('ROLE_USER', $user->getRoles());
        $this->assertNotNull($user->getCreatedAt());
        $this->assertNull($user->getUpdatedAt());
    }

    public function testSetEmail(): void
    {
        $user = new User();

        $user->setEmail('john@example.com');

        $this->assertEquals('john@example.com', $user->getEmail());
        $this->assertEquals('john@example.com', $user->getUserIdentifier());
    }

    public function testSetFirstName(): void
    {
        $user = new User();

        $user->setFirstName('John');

        $this->assertEquals('John', $user->getFirstName());
    }

    public function testSetLastName(): void
    {
        $user = new User();

        $user->setLastName('Doe');

        $this->assertEquals('Doe', $user->getLastName());
    }

    public function testSetPhone(): void
    {
        $user = new User();

        $user->setPhone('+33612345678');

        $this->assertEquals('+33612345678', $user->getPhone());
    }

    public function testSetPhoneNull(): void
    {
        $user = new User();
        $user->setPhone('+33612345678');

        $user->setPhone(null);

        $this->assertNull($user->getPhone());
    }

    public function testSetPassword(): void
    {
        $user = new User();

        $user->setPassword('hashed_password');

        $this->assertEquals('hashed_password', $user->getPassword());
    }

    public function testPlainPassword(): void
    {
        $user = new User();

        $user->setPlainPassword('mypassword123');

        $this->assertEquals('mypassword123', $user->getPlainPassword());
    }

    public function testEraseCredentials(): void
    {
        $user = new User();
        $user->setPlainPassword('mypassword123');

        $user->eraseCredentials();

        $this->assertNull($user->getPlainPassword());
    }

    public function testSetRoles(): void
    {
        $user = new User();

        $user->setRoles(['ROLE_ADMIN']);

        $roles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles); // Always includes ROLE_USER
    }

    public function testRolesAlwaysIncludesRoleUser(): void
    {
        $user = new User();

        $user->setRoles(['ROLE_ADMIN', 'ROLE_MANAGER']);

        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_MANAGER', $roles);
    }

    public function testRolesAreUnique(): void
    {
        $user = new User();

        $user->setRoles(['ROLE_USER', 'ROLE_USER', 'ROLE_ADMIN']);

        $roles = $user->getRoles();
        $this->assertCount(2, $roles);
    }

    public function testSetUpdatedAt(): void
    {
        $user = new User();
        $now = new DateTimeImmutable();

        $user->setUpdatedAt($now);

        $this->assertEquals($now, $user->getUpdatedAt());
    }

    public function testSetCreatedAt(): void
    {
        $user = new User();
        $now = new DateTimeImmutable('2024-01-01');

        $user->setCreatedAt($now);

        $this->assertEquals($now, $user->getCreatedAt());
    }

    public function testGetOrdersUrl(): void
    {
        $user = new User();

        $this->assertEquals('/api/auth/me/orders', $user->getOrdersUrl());
    }

    public function testGetLoyaltyUrl(): void
    {
        $user = new User();

        $this->assertEquals('/api/auth/me/loyalty', $user->getLoyaltyUrl());
    }

    public function testSetLoyaltyAccount(): void
    {
        $user = new User();
        $loyaltyAccount = new LoyaltyAccount();

        $user->setLoyaltyAccount($loyaltyAccount);

        $this->assertSame($loyaltyAccount, $user->getLoyaltyAccount());
        $this->assertSame($user, $loyaltyAccount->getUser());
    }

    public function testSetLoyaltyAccountNull(): void
    {
        $user = new User();
        $loyaltyAccount = new LoyaltyAccount();
        $user->setLoyaltyAccount($loyaltyAccount);

        $user->setLoyaltyAccount(null);

        $this->assertNull($user->getLoyaltyAccount());
    }

    public function testUserIdentifierIsEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->assertEquals('test@example.com', $user->getUserIdentifier());
    }

    public function testUserIdentifierWithNullEmail(): void
    {
        $user = new User();

        $this->assertEquals('', $user->getUserIdentifier());
    }
}
