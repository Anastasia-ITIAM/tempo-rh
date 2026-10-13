<?php

namespace App\Enum\LeaveRequest;

enum Status: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case DENIED = 'denied';
    case INVALID = 'invalid';
    case CANCELLATION_REQUESTED = 'cancellation_requested';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::CONFIRMED => 'Acceptée',
            self::DENIED => 'Refusée',
            self::INVALID => 'Invalide',
            self::CANCELLATION_REQUESTED => 'Annulation demandée',
            self::CANCELLED => 'Annulée',
        };
    }

    public function type(): string
    {
        return match($this) {
            self::PENDING => 'info',
            self::CONFIRMED => 'success',
            self::DENIED, self::CANCELLATION_REQUESTED => 'danger',
            self::INVALID => 'warning',
            self::CANCELLED => 'light',
        };
    }
}
