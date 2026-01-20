<?php

declare(strict_types=1);

namespace App\DTO\Extra;

use Symfony\Component\Validator\Constraints as Assert;

final class RestockDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'La quantité est obligatoire')]
        #[Assert\Positive(message: 'La quantité doit être positive')]
        public readonly int $quantity,
    ) {
    }
}
