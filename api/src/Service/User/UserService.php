<?php

namespace App\Service\User;

use App\DTO\User\CreateUserDTO;
use App\DTO\User\UpdateUserDTO;
use App\Entity\User;
use App\Exception\UserAlreadyExistsException;
use App\Exception\UserNotFoundException;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    /**
     * Crée un nouvel utilisateur
     */
    public function createUser(CreateUserDTO $dto): User
    {
        // Vérifier si l'email existe déjà
        if ($this->userRepository->findOneBy(['email' => $dto->email])) {
            throw new UserAlreadyExistsException($dto->email);
        }

        $user = new User();
        $user->setEmail($dto->email);
        $user->setFirstName($dto->firstName);
        $user->setLastName($dto->lastName);
        $user->setPhone($dto->phone);

        // Hash du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);

        return $user;
    }

    /**
     * Met à jour un utilisateur
     */
    public function updateUser(int $id, UpdateUserDTO $dto): User
    {
        $user = $this->getUserById($id);

        if ($dto->email !== null && $dto->email !== $user->getEmail()) {
            // Vérifier si le nouvel email n'est pas déjà utilisé
            if ($this->userRepository->findOneBy(['email' => $dto->email])) {
                throw new UserAlreadyExistsException($dto->email);
            }
            $user->setEmail($dto->email);
        }

        if ($dto->firstName !== null) {
            $user->setFirstName($dto->firstName);
        }

        if ($dto->lastName !== null) {
            $user->setLastName($dto->lastName);
        }

        if ($dto->phone !== null) {
            $user->setPhone($dto->phone);
        }

        if ($dto->password !== null) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->password);
            $user->setPassword($hashedPassword);
        }

        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->userRepository->save($user);

        return $user;
    }

    /**
     * Récupère un utilisateur par son ID
     */
    public function getUserById(int $id): User
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw new UserNotFoundException($id);
        }

        return $user;
    }

    /**
     * Récupère tous les utilisateurs
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * Supprime un utilisateur
     */
    public function deleteUser(int $id): void
    {
        $user = $this->getUserById($id);
        $this->userRepository->remove($user);
    }

    /**
     * Récupère un utilisateur par son email
     */
    public function getUserByEmail(string $email): ?User
    {
        return $this->userRepository->findOneBy(['email' => $email]);
    }
}
