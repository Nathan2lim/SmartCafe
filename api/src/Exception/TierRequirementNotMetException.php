<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class TierRequirementNotMetException extends BadRequestHttpException
{
    public function __construct(string $requiredTier, string $currentTier)
    {
        parent::__construct(sprintf(
            'Cette récompense nécessite le niveau "%s", vous êtes actuellement "%s"',
            $requiredTier,
            $currentTier
        ));
    }
}
