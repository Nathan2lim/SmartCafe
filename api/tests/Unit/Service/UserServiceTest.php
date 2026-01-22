<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\User\CreateUserDTO;
use App\DTO\User\UpdateUserDTO;
use App\Entity\User;
use App\Exception\UserAlreadyExistsException;
use App\Exception\UserNotFoundException;
use App\Repository\UserRepository;
use App\Service\User\UserService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private MockObject&UserRepository $userRepository;
    private MockObject&UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $this->userService = new UserService(
            $this->userRepository,
            $this->passwordHasher,
        );
    }

    public function testCreateUserSuccess(): void
    {
        $dto = new CreateUserDTO(
            email: 'john@example.com',
            password: 'password123',
            firstName: 'John',
            lastName: 'Doe',
            phone: '+33612345678',
        );

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'john@example.com'])
            ->willReturn(null);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed_password');

        $this->userRepository
            ->expects($this->once())
            ->method('save');

        $user = $this->userService->createUser($dto);

        $this->assertEquals('john@example.com', $user->getEmail());
        $this->assertEquals('John', $user->getFirstName());
        $this->assertEquals('Doe', $user->getLastName());
        $this->assertEquals('+33612345678', $user->getPhone());
        $this->assertEquals('hashed_password', $user->getPassword());
    }

    public function testCreateUserWithExistingEmail(): void
    {
        $existingUser = new User();
        $existingUser->setEmail('existing@example.com');

        $dto = new CreateUserDTO(
            email: 'existing@example.com',
            password: 'password123',
            firstName: 'John',
            lastName: 'Doe',
        );

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'existing@example.com'])
            ->willReturn($existingUser);

        $this->expectException(UserAlreadyExistsException::class);
        $this->userService->createUser($dto);
    }

    public function testUpdateUserSuccess(): void
    {
        $user = $this->createUser(1, 'john@example.com', 'John', 'Doe');

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $dto = new UpdateUserDTO(
            firstName: 'Jane',
            lastName: 'Smith',
            phone: '+33698765432',
        );

        $this->userRepository
            ->expects($this->once())
            ->method('save');

        $updatedUser = $this->userService->updateUser(1, $dto);

        $this->assertEquals('Jane', $updatedUser->getFirstName());
        $this->assertEquals('Smith', $updatedUser->getLastName());
        $this->assertEquals('+33698765432', $updatedUser->getPhone());
    }

    public function testUpdateUserEmail(): void
    {
        $user = $this->createUser(1, 'john@example.com', 'John', 'Doe');

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'newemail@example.com'])
            ->willReturn(null);

        $dto = new UpdateUserDTO(email: 'newemail@example.com');

        $this->userRepository
            ->expects($this->once())
            ->method('save');

        $updatedUser = $this->userService->updateUser(1, $dto);

        $this->assertEquals('newemail@example.com', $updatedUser->getEmail());
    }

    public function testUpdateUserEmailAlreadyExists(): void
    {
        $user = $this->createUser(1, 'john@example.com', 'John', 'Doe');
        $existingUser = $this->createUser(2, 'existing@example.com', 'Jane', 'Doe');

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'existing@example.com'])
            ->willReturn($existingUser);

        $dto = new UpdateUserDTO(email: 'existing@example.com');

        $this->expectException(UserAlreadyExistsException::class);
        $this->userService->updateUser(1, $dto);
    }

    public function testUpdateUserPassword(): void
    {
        $user = $this->createUser(1, 'john@example.com', 'John', 'Doe');

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('new_hashed_password');

        $dto = new UpdateUserDTO(password: 'newpassword123');

        $this->userRepository
            ->expects($this->once())
            ->method('save');

        $updatedUser = $this->userService->updateUser(1, $dto);

        $this->assertEquals('new_hashed_password', $updatedUser->getPassword());
    }

    public function testUpdateUserNotFound(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $dto = new UpdateUserDTO(firstName: 'Jane');

        $this->expectException(UserNotFoundException::class);
        $this->userService->updateUser(999, $dto);
    }

    public function testGetUserByIdSuccess(): void
    {
        $user = $this->createUser(1, 'john@example.com', 'John', 'Doe');

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $result = $this->userService->getUserById(1);

        $this->assertSame($user, $result);
    }

    public function testGetUserByIdNotFound(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);
        $this->userService->getUserById(999);
    }

    public function testGetAllUsers(): void
    {
        $users = [
            $this->createUser(1, 'john@example.com', 'John', 'Doe'),
            $this->createUser(2, 'jane@example.com', 'Jane', 'Doe'),
        ];

        $this->userRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($users);

        $result = $this->userService->getAllUsers();

        $this->assertCount(2, $result);
    }

    public function testDeleteUserSuccess(): void
    {
        $user = $this->createUser(1, 'john@example.com', 'John', 'Doe');

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->userRepository
            ->expects($this->once())
            ->method('remove')
            ->with($user);

        $this->userService->deleteUser(1);
    }

    public function testDeleteUserNotFound(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(UserNotFoundException::class);
        $this->userService->deleteUser(999);
    }

    public function testGetUserByEmail(): void
    {
        $user = $this->createUser(1, 'john@example.com', 'John', 'Doe');

        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'john@example.com'])
            ->willReturn($user);

        $result = $this->userService->getUserByEmail('john@example.com');

        $this->assertSame($user, $result);
    }

    public function testGetUserByEmailNotFound(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'notfound@example.com'])
            ->willReturn(null);

        $result = $this->userService->getUserByEmail('notfound@example.com');

        $this->assertNull($result);
    }

    // Helper methods
    private function createUser(int $id, string $email, string $firstName, string $lastName): User
    {
        $user = new User();
        $reflection = new ReflectionClass($user);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($user, $id);

        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setPassword('hashed_password');

        return $user;
    }
}
