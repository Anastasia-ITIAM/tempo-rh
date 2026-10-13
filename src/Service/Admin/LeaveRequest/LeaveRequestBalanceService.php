<?php

namespace App\Service\Admin\LeaveRequest;

use App\Entity\LeaveRequest\LeaveRequest;
use App\Enum\LeaveRequest\LeaveTypeCode;
use App\Repository\LeaveTypeRepository;

readonly class LeaveRequestBalanceService
{
    public function __construct(
        private LeaveTypeRepository $leaveTypeRepository,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function deduct(LeaveRequest $request): void
    {
        if (!$request->isVacation()) {
            return;
        }

        $days = $request->getNumberOfDays();
        $rtt = $request->getRtt() ?? 0;
        $cpDays = $days - $rtt;

        $cpLeaveType = $this->leaveTypeRepository->findActiveLeaveType($request, LeaveTypeCode::CP);
        $rttLeaveType = $rtt > 0 ? $this->leaveTypeRepository->findActiveLeaveType($request, LeaveTypeCode::RTT) : null;

        if ($cpLeaveType && $cpDays > 0) {
            $cpLeaveType->setCurrentNumber($cpLeaveType->getCurrentNumber() - $cpDays);
        }

        if ($rttLeaveType && $rtt > 0) {
            $rttLeaveType->setCurrentNumber($rttLeaveType->getCurrentNumber() - $rtt);
        }
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function restore(LeaveRequest $request): void
    {
        if (!$request->isVacation()) {
            return;
        }

        $days = $request->getNumberOfDays();
        $rtt = $request->getRtt() ?? 0;
        $cpDays = $days - $rtt;

        $cpLeaveType = $this->leaveTypeRepository->findActiveLeaveType($request, LeaveTypeCode::CP);
        $rttLeaveType = $rtt > 0 ? $this->leaveTypeRepository->findActiveLeaveType($request, LeaveTypeCode::RTT) : null;

        if ($cpLeaveType && $cpDays > 0) {
            $cpLeaveType->setCurrentNumber($cpLeaveType->getCurrentNumber() + $cpDays);
        }

        if ($rttLeaveType && $rtt > 0) {
            $rttLeaveType->setCurrentNumber($rttLeaveType->getCurrentNumber() + $rtt);
        }
    }
}
