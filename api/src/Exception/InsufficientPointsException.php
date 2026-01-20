<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class InsufficientPointsException extends BadRequestHttpException
{
    public function __construct(int $required, int $available)
    {
        parent::__construct(sprintf(
            'Points insuffisants: %d requis, %d disponibles',
            $required,
            $available
        ));
    }
}
