<?php

declare(strict_types=1);

namespace App\DTO\Order;

use App\Enum\OrderStatus;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateOrderStatusDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le statut est obligatoire')]
        public readonly OrderStatus $status,
    ) {
    }
}
