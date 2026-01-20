<?php

namespace App\Enum;

enum RewardType: string
{
    case FREE_PRODUCT = 'free_product';
    case DISCOUNT_AMOUNT = 'discount_amount';
    case DISCOUNT_PERCENT = 'discount_percent';
    case FREE_EXTRA = 'free_extra';
    case DOUBLE_POINTS = 'double_points';

    public function getLabel(): string
    {
        return match ($this) {
            self::FREE_PRODUCT => 'Produit offert',
            self::DISCOUNT_AMOUNT => 'Réduction en euros',
            self::DISCOUNT_PERCENT => 'Réduction en pourcentage',
            self::FREE_EXTRA => 'Extra offert',
            self::DOUBLE_POINTS => 'Points doublés',
        };
    }
}
