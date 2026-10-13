<?php

namespace App\Repository;

use App\Entity\LeaveRequest\LeaveRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LeaveRequest>
 */
class LeaveRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LeaveRequest::class);
    }

    public function findRequestsBetween(\DateTimeInterface $start, \DateTimeInterface $end)
    {
        return $this->createQueryBuilder('r')
            ->join('r.period', 'p')
            ->andWhere('p.startedAt >= :start')
            ->andWhere('p.endedAt <= :end')
            ->setParameter('start', $start->format('Y-m-d'))
            ->setParameter('end', $end->format('Y-m-d'))
            ->andWhere('r.status = :confirmed')
            ->setParameter('confirmed', 'CONFIRMED')
            ->getQuery()
            ->getResult();
    }
}
