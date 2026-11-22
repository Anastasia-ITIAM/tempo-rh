<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\LeaveRequest\LeaveRequest;
use App\Entity\LeaveRequest\LeaveType;
use App\Entity\LeaveRequest\Period;
use App\Entity\LeaveRequest\Reason;
use App\Enum\LeaveRequest\LeaveTypeCode;
use App\Repository\LeaveTypeRepository;
use App\Service\Admin\LeaveRequest\LeaveRequestBalanceService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LeaveRequestBalanceService::class)]
final class LeaveRequestBalanceServiceTest extends TestCase
{
    private LeaveType $cp;
    private LeaveType $rtt;
    private LeaveRequestBalanceService $service;

    protected function setUp(): void
    {
        $this->cp = (new LeaveType())->setCode(LeaveTypeCode::CP)->setCurrentNumber(10);
        $this->rtt = (new LeaveType())->setCode(LeaveTypeCode::RTT)->setCurrentNumber(5);

        $repository = $this->createStub(LeaveTypeRepository::class);
        $repository->method('findActiveLeaveType')->willReturnCallback(
            fn (LeaveRequest $request, LeaveTypeCode $code): LeaveType => LeaveTypeCode::CP === $code ? $this->cp : $this->rtt
        );

        $this->service = new LeaveRequestBalanceService($repository);
    }

    public function testDeductSplitsDaysBetweenCpAndRtt(): void
    {
        // 5 jours ouvrés, dont 2 en RTT : 3 jours de CP et 2 jours de RTT
        $this->service->deduct($this->request('vacation', rtt: 2));

        self::assertSame(7.0, $this->cp->getCurrentNumber());
        self::assertSame(3.0, $this->rtt->getCurrentNumber());
    }

    public function testRestoreGivesBackTheDeductedDays(): void
    {
        $request = $this->request('vacation', rtt: 2);

        $this->service->deduct($request);
        $this->service->restore($request);

        self::assertSame(10.0, $this->cp->getCurrentNumber());
        self::assertSame(5.0, $this->rtt->getCurrentNumber());
    }

    public function testNonVacationReasonDoesNotChangeTheBalance(): void
    {
        $this->service->deduct($this->request('sick'));

        self::assertSame(10.0, $this->cp->getCurrentNumber());
        self::assertSame(5.0, $this->rtt->getCurrentNumber());
    }

    private function request(string $reasonType, float $rtt = 0): LeaveRequest
    {
        return (new LeaveRequest())
            ->setReason((new Reason())->setType($reasonType))
            ->setRtt($rtt)
            ->setPeriod(
                (new Period())
                    ->setStartedAt(new \DateTimeImmutable('2026-11-23 08:00'))
                    ->setEndedAt(new \DateTimeImmutable('2026-11-30 08:00'))
            );
    }
}
