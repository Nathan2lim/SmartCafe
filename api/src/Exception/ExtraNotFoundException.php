<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExtraNotFoundException extends NotFoundHttpException
{
    public function __construct(int $extraId)
    {
        parent::__construct(sprintf('Extra avec l\'ID %d non trouvé', $extraId));
    }
}
