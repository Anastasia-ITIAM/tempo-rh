<?php

namespace App\Enum\LeaveRequest;

enum LeaveTypeCode: string
{
    case RTT = 'RTT';
    case CP = 'CP';

    public function label(): string
    {
        return match($this) {
            self::RTT => 'RTT',
            self::CP => 'CP',
        };
    }

    public function codeForAlertMessage(): string
    {
        return match($this) {
            self::RTT => 'de RTT',
            self::CP => 'de CP',
        };
    }
}
