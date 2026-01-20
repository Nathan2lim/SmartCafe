<?php

declare(strict_types=1);

namespace App\DTO\Extra;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateExtraDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom est obligatoire')]
        public readonly string $name,
        #[Assert\NotBlank(message: 'Le prix est obligatoire')]
        #[Assert\Positive(message: 'Le prix doit être positif')]
        public readonly string $price,
        #[Assert\NotBlank(message: 'La quantité en stock est obligatoire')]
        #[Assert\PositiveOrZero(message: 'La quantité en stock doit être positive ou nulle')]
        public readonly int $stockQuantity,
        public readonly ?string $description = null,
        #[Assert\Positive(message: 'Le seuil de stock bas doit être positif')]
        public readonly int $lowStockThreshold = 10,
        public readonly bool $available = true,
    ) {
    }
}
