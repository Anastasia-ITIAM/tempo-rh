<?php

namespace App\Enum\TimeSheet;

enum Status: string
{
    case DRAFT = 'brouillon';

    case SUBMITTED = 'soumis';
    case APPROVED = 'approuvé';
    case REJECTED = 'rejeté';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Brouillon',
            self::SUBMITTED => 'Soumise',
            self::APPROVED => 'Validée',
            self::REJECTED => 'Rejetée',

        };
    }

    public function type(): string
    {
        return match($this) {
            self::APPROVED => 'success',
            self::SUBMITTED => 'warning',
            self::DRAFT => 'secondary',
            self::REJECTED => 'danger',
        };
    }
}
