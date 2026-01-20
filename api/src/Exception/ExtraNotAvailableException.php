<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ExtraNotAvailableException extends BadRequestHttpException
{
    public function __construct(string $extraName)
    {
        parent::__construct(sprintf('L\'extra "%s" n\'est pas disponible', $extraName));
    }
}
