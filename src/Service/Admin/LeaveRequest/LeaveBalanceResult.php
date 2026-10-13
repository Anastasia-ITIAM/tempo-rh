<?php

namespace App\Service\Admin\LeaveRequest;

class LeaveBalanceResult
{
    public function __construct(
        public readonly bool $valid,
        public readonly float $remaining = 0,
        public readonly ?string $message = null
    ) {
    }
}
