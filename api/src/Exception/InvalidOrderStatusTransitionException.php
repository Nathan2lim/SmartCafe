<?php

namespace App\Exception;

use App\Enum\OrderStatus;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class InvalidOrderStatusTransitionException extends BadRequestHttpException
{
    public function __construct(OrderStatus $currentStatus, OrderStatus $newStatus)
    {
        parent::__construct(sprintf(
            'Transition de statut invalide : impossible de passer de "%s" à "%s"',
            $currentStatus->label(),
            $newStatus->label()
        ));
    }
}
