<?php

namespace App\DTO\Order;

use Symfony\Component\Validator\Constraints as Assert;

final class OrderItemExtraDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'L\'ID de l\'extra est obligatoire')]
        #[Assert\Positive(message: 'L\'ID de l\'extra doit être positif')]
        public readonly int $extraId,

        #[Assert\Positive(message: 'La quantité doit être positive')]
        public readonly int $quantity = 1,
    ) {}
}
