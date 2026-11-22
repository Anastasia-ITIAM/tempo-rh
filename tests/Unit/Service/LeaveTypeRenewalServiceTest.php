<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\LeaveRequest\LeaveType;
use App\Entity\User;
use App\Enum\LeaveRequest\LeaveTypeCode;
use App\Repository\LeaveTypeRepository;
use App\Service\Admin\LeaveRequest\LeaveTypeRenewalService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LeaveTypeRenewalService::class)]
final class LeaveTypeRenewalServiceTest extends TestCase
{
    public function testExpiredLeaveTypeIsArchivedAndRenewedForOneYear(): void
    {
        $user = new User();
        $expired = (new LeaveType())
            ->setUser($user)
            ->setCode(LeaveTypeCode::CP)
            ->setMaxInitialNumber(25)
            ->setCurrentNumber(4)
            ->setStartDate(new \DateTimeImmutable('2025-06-01'))
            ->setEndDate(new \DateTimeImmutable('2026-05-31'));

        $repository = $this->createStub(LeaveTypeRepository::class);
        $repository->method('findExpiredToRenew')->willReturn([$expired]);

        $persisted = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });
        $entityManager->expects(self::once())->method('flush');

        (new LeaveTypeRenewalService($entityManager, $repository))->renewIfExpired();

        self::assertNotNull($expired->getArchivedAt());

        $renewed = array_values(array_filter(
            $persisted,
            fn (object $entity): bool => $entity instanceof LeaveType && $entity !== $expired
        ));
        /** @var LeaveType[] $renewed */
        self::assertCount(1, $renewed);
        self::assertSame($user, $renewed[0]->getUser());
        self::assertSame(LeaveTypeCode::CP, $renewed[0]->getCode());
        self::assertSame(25.0, $renewed[0]->getCurrentNumber());
        self::assertSame('2026-06-01', $renewed[0]->getStartDate()->format('Y-m-d'));
        self::assertSame('2027-05-31', $renewed[0]->getEndDate()->format('Y-m-d'));
    }

    public function testNothingIsWrittenWhenNoLeaveTypeHasExpired(): void
    {
        $repository = $this->createStub(LeaveTypeRepository::class);
        $repository->method('findExpiredToRenew')->willReturn([]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        (new LeaveTypeRenewalService($entityManager, $repository))->renewIfExpired();
    }
}
