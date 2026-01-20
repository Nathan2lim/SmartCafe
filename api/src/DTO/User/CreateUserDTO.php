<?php

declare(strict_types=1);

namespace App\DTO\User;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateUserDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'L\'email est obligatoire')]
        #[Assert\Email(message: 'L\'email n\'est pas valide')]
        public readonly string $email,
        #[Assert\NotBlank(message: 'Le mot de passe est obligatoire')]
        #[Assert\Length(min: 4, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères')]
        public readonly string $password,
        #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
        public readonly string $firstName,
        #[Assert\NotBlank(message: 'Le nom est obligatoire')]
        public readonly string $lastName,
        public readonly ?string $phone = null,
    ) {
    }
}
