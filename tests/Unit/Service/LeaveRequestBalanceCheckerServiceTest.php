<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Constant\Constant;
use App\Entity\LeaveRequest\LeaveRequest;
use App\Entity\LeaveRequest\LeaveType;
use App\Entity\LeaveRequest\Period;
use App\Entity\LeaveRequest\Reason;
use App\Enum\LeaveRequest\LeaveTypeCode;
use App\Repository\LeaveTypeRepository;
use App\Service\Admin\LeaveRequest\LeaveRequestBalanceCheckerService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LeaveRequestBalanceCheckerService::class)]
final class LeaveRequestBalanceCheckerServiceTest extends TestCase
{
    public function testRequestWithoutDaysIsInvalid(): void
    {
        $result = $this->checker()->check($this->request('vacation', null, null));

        self::assertFalse($result->valid);
        self::assertSame(Constant::INVALID_DAYS, $result->message);
    }

    public function testRttIsNotAllowedForANonVacationReason(): void
    {
        $request = $this->request('sick', '2026-11-23 08:00', '2026-11-25 08:00', rtt: 1);

        $result = $this->checker()->check($request);

        self::assertFalse($result->valid);
        self::assertSame(Constant::RTT_NOT_ALLOWED, $result->message);
    }

    public function testNonVacationReasonDoesNotUseTheBalance(): void
    {
        $request = $this->request('sick', '2026-11-23 08:00', '2026-11-25 08:00');

        self::assertTrue($this->checker()->check($request)->valid);
    }

    public function testVacationIsValidWhenTheBalanceIsEnough(): void
    {
        $request = $this->request('vacation', '2026-11-23 08:00', '2026-11-30 08:00');

        $result = $this->checker(cp: 10)->check($request);

        self::assertTrue($result->valid);
    }

    public function testVacationIsRefusedWhenCpBalanceIsTooLow(): void
    {
        $request = $this->request('vacation', '2026-11-23 08:00', '2026-11-30 08:00');

        $result = $this->checker(cp: 3)->check($request);

        self::assertFalse($result->valid);
        self::assertSame(3.0, $result->remaining);
        self::assertSame(Constant::INSUFFICIENT_BALANCE_USER . ' Il vous reste 3 jours de CP.', $result->message);
    }

    public function testAdminSeesTheEmployeeBalanceMessage(): void
    {
        $request = $this->request('vacation', '2026-11-23 08:00', '2026-11-30 08:00');

        $result = $this->checker(cp: 1)->check($request, isAdmin: true);

        self::assertSame(Constant::INSUFFICIENT_BALANCE_ADMIN . ' Il lui reste 1 jour de CP.', $result->message);
    }

    public function testVacationIsRefusedWhenRttBalanceIsTooLow(): void
    {
        $request = $this->request('vacation', '2026-11-23 08:00', '2026-11-30 08:00', rtt: 2);

        $result = $this->checker(cp: 10, rtt: 1)->check($request);

        self::assertFalse($result->valid);
        self::assertSame(1.0, $result->remaining);
    }

    public function testExpiredLeaveTypeIsRefused(): void
    {
        $request = $this->request('vacation', '2026-11-23 08:00', '2026-11-24 08:00');

        $result = $this->checker(cp: 10, cpEndDate: '2020-05-31')->check($request);

        self::assertFalse($result->valid);
        self::assertSame(Constant::TYPE_EXPIRED, $result->message);
    }

    private function checker(float $cp = 0, float $rtt = 0, string $cpEndDate = '2099-05-31'): LeaveRequestBalanceCheckerService
    {
        $cpType = $this->leaveType(LeaveTypeCode::CP, $cp, $cpEndDate);
        $rttType = $this->leaveType(LeaveTypeCode::RTT, $rtt, '2099-05-31');

        $repository = $this->createStub(LeaveTypeRepository::class);
        $repository->method('findOneBy')->willReturnCallback(
            fn (array $criteria): LeaveType => LeaveTypeCode::CP === $criteria['code'] ? $cpType : $rttType
        );

        return new LeaveRequestBalanceCheckerService($repository);
    }

    private function leaveType(LeaveTypeCode $code, float $current, string $endDate): LeaveType
    {
        return (new LeaveType())
            ->setCode($code)
            ->setCurrentNumber($current)
            ->setEndDate(new \DateTimeImmutable($endDate));
    }

    private function request(string $reasonType, ?string $start, ?string $end, float $rtt = 0): LeaveRequest
    {
        $request = (new LeaveRequest())
            ->setReason((new Reason())->setType($reasonType))
            ->setRtt($rtt);

        if (null !== $start && null !== $end) {
            $request->setPeriod(
                (new Period())
                    ->setStartedAt(new \DateTimeImmutable($start))
                    ->setEndedAt(new \DateTimeImmutable($end))
            );
        }

        return $request;
    }
}
