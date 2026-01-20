<?php

namespace App\DTO\Order;

use Symfony\Component\Validator\Constraints as Assert;

final class OrderItemDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'L\'ID du produit est obligatoire')]
        #[Assert\Positive]
        public readonly int $productId,

        #[Assert\NotBlank(message: 'La quantité est obligatoire')]
        #[Assert\Positive(message: 'La quantité doit être positive')]
        public readonly int $quantity = 1,

        public readonly ?string $specialInstructions = null,

        /** @var OrderItemExtraDTO[] */
        #[Assert\Valid]
        public readonly array $extras = [],
    ) {}
}
