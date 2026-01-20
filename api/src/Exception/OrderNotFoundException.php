<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class OrderNotFoundException extends NotFoundHttpException
{
    public function __construct(int $id)
    {
        parent::__construct(sprintf('Commande #%d non trouvée', $id));
    }
}
