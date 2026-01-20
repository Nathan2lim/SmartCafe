<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class RewardNotAvailableException extends BadRequestHttpException
{
    public function __construct(string $rewardName)
    {
        parent::__construct(sprintf('La récompense "%s" n\'est pas disponible', $rewardName));
    }
}
