<?php

declare(strict_types=1);

namespace App\DTO\Order;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateOrderDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Les articles sont obligatoires')]
        #[Assert\Count(min: 1, minMessage: 'La commande doit contenir au moins un article')]
        #[Assert\Valid]
        public readonly array $items,
        public readonly ?string $notes = null,
        public readonly ?string $tableNumber = null,
    ) {
    }
}
