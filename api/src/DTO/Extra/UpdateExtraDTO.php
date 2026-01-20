<?php

declare(strict_types=1);

namespace App\DTO\Extra;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateExtraDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        #[Assert\Positive(message: 'Le prix doit être positif')]
        public readonly ?string $price = null,
        #[Assert\PositiveOrZero(message: 'La quantité en stock doit être positive ou nulle')]
        public readonly ?int $stockQuantity = null,
        #[Assert\Positive(message: 'Le seuil de stock bas doit être positif')]
        public readonly ?int $lowStockThreshold = null,
        public readonly ?bool $available = null,
    ) {
    }
}
