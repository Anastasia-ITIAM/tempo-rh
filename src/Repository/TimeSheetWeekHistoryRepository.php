<?php

namespace App\Repository;

use App\Entity\TimeSheet\TimeSheetWeekHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TimeSheetWeekHistory>
 */
class TimeSheetWeekHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeSheetWeekHistory::class);
    }
}
