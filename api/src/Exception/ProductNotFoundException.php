<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProductNotFoundException extends NotFoundHttpException
{
    public function __construct(int $id)
    {
        parent::__construct(sprintf('Produit #%d non trouvé', $id));
    }
}
