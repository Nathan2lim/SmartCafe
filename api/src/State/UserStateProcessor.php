<?php

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\User\CreateUserDTO;
use App\DTO\User\UpdateUserDTO;
use App\Entity\User;
use App\Service\User\UserService;

final class UserStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ?User
    {
        // Suppression
        if ($operation instanceof DeleteOperationInterface) {
            $this->userService->deleteUser($uriVariables['id']);
            return null;
        }

        // Création
        if ($data instanceof User && $data->getId() === null) {
            $dto = new CreateUserDTO(
                email: $data->getEmail(),
                password: $data->getPlainPassword(),
                firstName: $data->getFirstName(),
                lastName: $data->getLastName(),
                phone: $data->getPhone(),
            );
            return $this->userService->createUser($dto);
        }

        // Mise à jour
        if ($data instanceof User && $data->getId() !== null) {
            $dto = new UpdateUserDTO(
                email: $data->getEmail(),
                password: $data->getPlainPassword(),
                firstName: $data->getFirstName(),
                lastName: $data->getLastName(),
                phone: $data->getPhone(),
            );
            return $this->userService->updateUser($data->getId(), $dto);
        }

        return $data;
    }
}
