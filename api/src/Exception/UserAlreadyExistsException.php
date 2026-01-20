<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class UserAlreadyExistsException extends ConflictHttpException
{
    public function __construct(string $email)
    {
        parent::__construct(sprintf('Un utilisateur avec l\'email "%s" existe déjà', $email));
    }
}
