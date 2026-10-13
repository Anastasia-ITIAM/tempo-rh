<?php

namespace App\Service\Admin\LeaveRequest;

use App\Constant\Constant;
use App\Entity\LeaveRequest\LeaveRequest;
use App\Enum\LeaveRequest\LeaveTypeCode;
use App\Repository\LeaveTypeRepository;

readonly class LeaveRequestBalanceCheckerService
{
    public function __construct(
        private LeaveTypeRepository $leaveTypeRepository,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function check(LeaveRequest $request, bool $isAdmin = false): LeaveBalanceResult
    {
        $isVacation = $request->isVacation();

        $days = (float) $request->getNumberOfDays();

        $rtt = (float) $request->getRtt();
        $period = (float) ($request->getPeriod()?->getDuration() ?? 0);

        $rtt = max(0, min($rtt, $days));
        $cpDays = $days - $rtt;

        $rawStart = $request->getPeriod()?->getStartedAt();
        $rawEnd = $request->getPeriod()?->getEndedAt();

        if ($days <= 0) {
            return new LeaveBalanceResult(false, 0, Constant::INVALID_DAYS);
        }

        if (!$isVacation) {

            if ($rtt > 0) {
                return new LeaveBalanceResult(false, 0, Constant::RTT_NOT_ALLOWED);
            }

            return new LeaveBalanceResult(true);
        }

        if ($rtt > $period) {
            return new LeaveBalanceResult(false, 0, Constant::RTT_DAYS_MISMATCH);
        }

        if (fmod($rtt, 1) === 0.5 && $rawStart?->format('H:i') === '08:00' && $rawEnd?->format('H:i') === '08:00') {
            return new LeaveBalanceResult(false, 0, Constant::HALF_DAY_REQUIRES_MIDDAY);
        }

        $cpLeaveType = $this->leaveTypeRepository->findOneBy([
            'user' => $request->getRequestedBy(),
            'code' => LeaveTypeCode::CP,
            'archivedAt' => null,
        ]);

        $rttLeaveType = $rtt > 0
            ? $this->leaveTypeRepository->findOneBy([
                'user' => $request->getRequestedBy(),
                'code' => LeaveTypeCode::RTT,
                'archivedAt' => null,
            ])
            : null;

        if ($cpLeaveType?->isExpired()) {
            return new LeaveBalanceResult(false, 0, Constant::TYPE_EXPIRED);
        }

        if ($rttLeaveType?->isExpired()) {
            return new LeaveBalanceResult(false, 0, Constant::TYPE_EXPIRED);
        }

        $cpRemaining = $cpLeaveType?->getCurrentNumber() ?? 0;
        $rttRemaining = $rttLeaveType?->getCurrentNumber() ?? 0;

        if ($cpRemaining < $cpDays) {

            $baseMessage = $isAdmin
                ? Constant::INSUFFICIENT_BALANCE_ADMIN
                : Constant::INSUFFICIENT_BALANCE_USER;

            $pronoun = $isAdmin ? 'lui' : 'vous';
            $dayLabel = $cpRemaining > 1 ? 'jours' : 'jour';

            $message = sprintf(
                '%s Il %s reste %s %s de CP.',
                $baseMessage,
                $pronoun,
                $cpRemaining,
                $dayLabel
            );

            return new LeaveBalanceResult(false, $cpRemaining, $message);
        }

        if ($rttRemaining < $rtt) {

            $baseMessage = $isAdmin
                ? Constant::INSUFFICIENT_BALANCE_ADMIN
                : Constant::INSUFFICIENT_BALANCE_USER;

            $pronoun = $isAdmin ? 'lui' : 'vous';
            $dayLabel = $rttRemaining > 1 ? 'jours' : 'jour';

            $message = sprintf(
                '%s Il %s reste %s %s de RTT.',
                $baseMessage,
                $pronoun,
                $rttRemaining,
                $dayLabel
            );

            return new LeaveBalanceResult(false, $rttRemaining, $message);
        }

        return new LeaveBalanceResult(true);
    }
}
