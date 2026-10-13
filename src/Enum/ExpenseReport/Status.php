<?php

namespace App\Enum\ExpenseReport;

enum Status: string
{
    case PENDING = 'pending';
    case REFUNDED = 'refunded';
    case INVALID = 'invalid';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::REFUNDED => 'Remboursée',
            self::INVALID => 'Invalide',
        };
    }

    public function type(): string
    {
        return match($this) {
            self::PENDING => 'info',
            self::REFUNDED => 'success',
            self::INVALID => 'warning',
        };
    }
}
