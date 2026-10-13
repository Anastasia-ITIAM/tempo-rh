<?php

namespace App\Service\Admin\LeaveRequest;

use App\Entity\LeaveRequest\LeaveRequest;
use App\Enum\LeaveRequest\LeaveTypeCode;
use App\Repository\LeaveTypeRepository;

readonly class LeaveRequestTypeService
{
    public function __construct(
        private LeaveTypeRepository $leaveTypeRepository
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function applyLeaveRequestType(LeaveRequest $leaveRequest): void
    {
        $reason = $leaveRequest->getReason();

        if ($reason?->getType() !== 'vacation') {
            $leaveRequest->setLeaveType(null);

            return;
        }

        $user = $leaveRequest->getRequestedBy();
        $days = $leaveRequest->getNumberOfDays();
        $rtt = (float) ($leaveRequest->getRtt() ?? 0);

        $code = $rtt >= $days
            ? LeaveTypeCode::RTT
            : LeaveTypeCode::CP;

        $leaveType = $this->leaveTypeRepository->findOneBy([
            'user' => $user,
            'code' => $code,
        ]);

        $leaveRequest->setLeaveType($leaveType);
    }
}
