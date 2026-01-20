<?php

namespace App\Enum;

enum OrderStatus: string
{
    case PENDING = 'pending';           // En attente de validation
    case CONFIRMED = 'confirmed';       // Confirmée
    case PREPARING = 'preparing';       // En préparation
    case READY = 'ready';               // Prête à être servie/récupérée
    case DELIVERED = 'delivered';       // Livrée/Servie
    case CANCELLED = 'cancelled';       // Annulée

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::CONFIRMED => 'Confirmée',
            self::PREPARING => 'En préparation',
            self::READY => 'Prête',
            self::DELIVERED => 'Livrée',
            self::CANCELLED => 'Annulée',
        };
    }

    /**
     * Vérifie si la transition vers un nouveau statut est autorisée
     */
    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [self::CONFIRMED, self::CANCELLED]),
            self::CONFIRMED => in_array($newStatus, [self::PREPARING, self::CANCELLED]),
            self::PREPARING => in_array($newStatus, [self::READY, self::CANCELLED]),
            self::READY => in_array($newStatus, [self::DELIVERED]),
            self::DELIVERED => false,
            self::CANCELLED => false,
        };
    }

    /**
     * Retourne les statuts suivants possibles
     * @return OrderStatus[]
     */
    public function nextPossibleStatuses(): array
    {
        return match($this) {
            self::PENDING => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::PREPARING, self::CANCELLED],
            self::PREPARING => [self::READY, self::CANCELLED],
            self::READY => [self::DELIVERED],
            self::DELIVERED => [],
            self::CANCELLED => [],
        };
    }
}
