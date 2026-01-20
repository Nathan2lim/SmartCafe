<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class RewardNotFoundException extends NotFoundHttpException
{
    public function __construct(int $rewardId)
    {
        parent::__construct(\sprintf('Récompense avec l\'ID %d non trouvée', $rewardId));
    }
}
