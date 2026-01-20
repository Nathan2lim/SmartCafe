<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class InsufficientStockException extends BadRequestHttpException
{
    public function __construct(string $itemName, int $requested, int $available)
    {
        parent::__construct(sprintf(
            'Stock insuffisant pour "%s": %d demandé(s), %d disponible(s)',
            $itemName,
            $requested,
            $available
        ));
    }
}
