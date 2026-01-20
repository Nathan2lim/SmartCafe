<?php

declare(strict_types=1);

namespace App\Enum;

enum LoyaltyTransactionType: string
{
    case EARN = 'earn';
    case REDEEM = 'redeem';
    case BONUS = 'bonus';
    case EXPIRED = 'expired';
    case ADJUSTMENT = 'adjustment';

    public function getLabel(): string
    {
        return match ($this) {
            self::EARN => 'Points gagnés',
            self::REDEEM => 'Points utilisés',
            self::BONUS => 'Bonus',
            self::EXPIRED => 'Points expirés',
            self::ADJUSTMENT => 'Ajustement',
        };
    }
}
