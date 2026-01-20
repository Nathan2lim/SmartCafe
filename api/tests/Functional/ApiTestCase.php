<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase as BaseApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

abstract class ApiTestCase extends BaseApiTestCase
{
    protected ?Client $client = null;
    protected ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
        $this->entityManager = null;
    }

    protected function createUser(
        string $email = 'test@smartcafe.fr',
        string $password = 'password123',
        array $roles = ['ROLE_USER'],
    ): User {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setRoles($roles);

        $hashedPassword = static::getContainer()
            ->get('security.user_password_hasher')
            ->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    protected function createAdminUser(
        string $email = 'admin@smartcafe.fr',
        string $password = 'admin123',
    ): User {
        return $this->createUser($email, $password, ['ROLE_ADMIN']);
    }

    protected function getJwtToken(User $user): string
    {
        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        return $jwtManager->create($user);
    }

    protected function createAuthenticatedClient(User $user): Client
    {
        $token = $this->getJwtToken($user);

        return static::createClient([], [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    protected function request(
        string $method,
        string $url,
        array $options = [],
        ?User $user = null,
    ): \Symfony\Contracts\HttpClient\ResponseInterface {
        $client = $user ? $this->createAuthenticatedClient($user) : $this->client;

        return $client->request($method, $url, $options);
    }
}
