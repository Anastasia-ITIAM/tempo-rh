<?php

namespace App\Repository;

use App\Entity\TimeSheet\Client;
use App\Entity\TimeSheet\Project;
use App\Entity\TimeSheet\Task;
use App\Entity\TimeSheet\TimeSheetEntry;
use App\Entity\TimeSheet\TimeSheetWeek;
use App\Enum\TimeSheet\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends ServiceEntityRepository<TimeSheetEntry>
 */
class TimeSheetEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeSheetEntry::class);
    }

    public function getTotalHoursByWeek(int $weekId): float
    {
        return (float) $this->createQueryBuilder('e')
            ->select('SUM(e.hours)')
            ->where('e.timeSheetWeek = :week')
            ->setParameter('week', $weekId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTotalHoursForUser(int $userId): float
    {
        return (float) $this->createQueryBuilder('e')
            ->select('SUM(e.hours)')
            ->join('e.timeSheetWeek', 'w')
            ->where('w.user = :user')
            ->setParameter('user', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOrCreateEntry(
        TimeSheetWeek $timesheet,
        \DateTime $date,
        Client $client,
        Project $project,
        Task $task
    ): TimeSheetEntry {
        $entry = $this->createQueryBuilder('e')
            ->where('e.timeSheetWeek = :week')
            ->andWhere('e.date = :date')
            ->andWhere('e.client = :client')
            ->andWhere('e.project = :project')
            ->andWhere('e.task = :task')
            ->setParameter('week', $timesheet)
            ->setParameter('date', $date)
            ->setParameter('client', $client)
            ->setParameter('project', $project)
            ->setParameter('task', $task)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$entry) {
            $entry = new TimeSheetEntry();
            $entry->setTimeSheetWeek($timesheet);
            $entry->setDate($date);
            $entry->setClient($client);
            $entry->setProject($project);
            $entry->setTask($task);
        }

        return $entry;
    }

    public function deleteRow(TimeSheetWeek $timesheet, Client $client, Project $project, Task $task): void
    {
        $entries = $this->createQueryBuilder('e')
            ->where('e.timeSheetWeek = :week')
            ->andWhere('e.client = :client')
            ->andWhere('e.project = :project')
            ->andWhere('e.task = :task')
            ->setParameter('week', $timesheet)
            ->setParameter('client', $client)
            ->setParameter('project', $project)
            ->setParameter('task', $task)
            ->getQuery()
            ->getResult();

        foreach ($entries as $entry) {
            $this->getEntityManager()->remove($entry);
        }
    }

    /**
     * Récupérer les entrées de temps filtrées pour l'export
     */
    public function findForExport(Request $request): array
    {
        $dateFrom = $request->query->get('dateFrom', new \DateTime('first day of this month')->format('Y-m-d'));
        $dateTo = $request->query->get('dateTo', new \DateTime('last day of this month')->format('Y-m-d'));
        $userId = $request->query->get('userId');
        $clientId = $request->query->get('clientId');
        $status = $request->query->get('status');

        try {
            $fromDate = new \DateTime($dateFrom);
            $toDate = new \DateTime($dateTo);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Format de date invalide');
        }

        $toDateBound = (clone $toDate)->modify('+1 day');

        $qb = $this->createQueryBuilder('te')
            ->join('te.timeSheetWeek', 'tw')->addSelect('tw')
            ->join('tw.user', 'u')->addSelect('u')
            ->leftJoin('te.client', 'c')->addSelect('c')
            ->leftJoin('te.project', 'p')->addSelect('p')
            ->leftJoin('te.task', 't')->addSelect('t')
            ->andWhere('te.date >= :dateFrom')
            ->andWhere('te.date < :dateTo')
            ->setParameter('dateFrom', $fromDate)
            ->setParameter('dateTo', $toDateBound)
            ->orderBy('u.lastName', 'ASC')
            ->addOrderBy('u.firstName', 'ASC')
            ->addOrderBy('te.date', 'ASC');

        if ($status) {
            try {
                $enumStatus = Status::from($status);
            } catch (\ValueError $e) {
                throw new \InvalidArgumentException('Statut invalide: ' . $status);
            }
            $qb->andWhere('tw.status = :status')->setParameter('status', $enumStatus);
        } else {
            $qb->andWhere('tw.status != :draft')->setParameter('draft', Status::DRAFT);
        }

        if ($userId) {
            $qb->andWhere('tw.user = :userId')->setParameter('userId', $userId);
        }

        if ($clientId) {
            $qb->andWhere('c.id = :clientId')->setParameter('clientId', $clientId);
        }

        return [
            'entries' => $qb->getQuery()->getResult(),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ];
    }
}
