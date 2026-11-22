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

    /**
     * Congés confirmés sur la période, avec leur période, leur raison et leur demandeur
     * chargés dans la même requête (évite une requête par congé, problème N+1).
     *
     * @return LeaveRequest[]
     */
    public function findRequestsBetween(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.period', 'p')
            ->addSelect('p')
            ->leftJoin('r.reason', 'reason')
            ->addSelect('reason')
            ->join('r.requestedBy', 'u')
            ->addSelect('u')
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
