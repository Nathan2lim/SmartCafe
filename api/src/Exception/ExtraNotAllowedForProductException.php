<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ExtraNotAllowedForProductException extends BadRequestHttpException
{
    public function __construct(string $extraName, string $productName)
    {
        parent::__construct(\sprintf(
            'L\'extra "%s" n\'est pas autorisé pour le produit "%s"',
            $extraName,
            $productName,
        ));
    }
}
