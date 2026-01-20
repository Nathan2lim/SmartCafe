<?php

namespace App\DTO\User;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateUserDTO
{
    public function __construct(
        #[Assert\Email(message: 'L\'email n\'est pas valide')]
        public readonly ?string $email = null,

        #[Assert\Length(min: 4, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères')]
        public readonly ?string $password = null,

        public readonly ?string $firstName = null,

        public readonly ?string $lastName = null,

        public readonly ?string $phone = null,
    ) {}
}
