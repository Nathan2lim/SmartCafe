<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ProductNotAvailableException extends BadRequestHttpException
{
    public function __construct(string $productName)
    {
        parent::__construct(\sprintf('Le produit "%s" n\'est pas disponible', $productName));
    }
}
