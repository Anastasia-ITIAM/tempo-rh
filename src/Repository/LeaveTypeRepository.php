<?php

namespace App\Repository;

use App\Entity\LeaveRequest\LeaveRequest;
use App\Entity\LeaveRequest\LeaveType;
use App\Enum\LeaveRequest\LeaveTypeCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LeaveType>
 */
class LeaveTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LeaveType::class);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function findActiveLeaveType(LeaveRequest $request, LeaveTypeCode $code)
    {
        $qb = $this->createQueryBuilder('lt');

        $qb
            ->where('lt.user = :user')
            ->andWhere('lt.code = :code')
            ->andWhere('lt.archivedAt IS NULL')
            ->andWhere('(lt.endDate IS NULL OR lt.endDate >= :today)')
            ->setParameter('user', $request->getRequestedBy())
            ->setParameter('code', $code)
            ->setParameter('today', new \DateTimeImmutable('today'));

        $leaveType = $qb->getQuery()->getOneOrNullResult();

        if (!$leaveType || $leaveType->isExpired()) {
            return null;
        }

        return $leaveType;
    }

    /**
     * Types de congés expirés et pas encore archivés : seules ces lignes sont à renouveler.
     *
     * @return LeaveType[]
     */
    public function findExpiredToRenew(\DateTimeImmutable $today): array
    {
        return $this->createQueryBuilder('lt')
            ->andWhere('lt.archivedAt IS NULL')
            ->andWhere('lt.endDate <= :today')
            ->setParameter('today', $today->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }
}
