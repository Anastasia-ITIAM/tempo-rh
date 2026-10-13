<?php

namespace App\Constant;

final class Constant
{
    public const string TYPE_EXPIRED = 'Ce type de congé est expiré.';

    public const string RTT_DAYS_MISMATCH = 'Le nombre de jours RTT ne correspond pas à la durée de la période sélectionnée.';
    public const string INVALID_DAYS = 'Les dates sélectionnées sont invalides';

    public const string HALF_DAY_REQUIRES_MIDDAY = 'Pour une demi-journée, choisissez midi au départ ou à la reprise.';
    public const string INSUFFICIENT_BALANCE_ADMIN = 'Solde insuffisant pour cet employé.';
    public const string INSUFFICIENT_BALANCE_USER = 'Solde insuffisant.';

    public const string RTT_NOT_ALLOWED = 'Les jours RTT ne sont pas autorisés pour cette raison.';
}
