<?php

namespace App\Repository;

use App\Entity\TimeSheet\TimeSheetWeek;
use App\Entity\User;
use App\Enum\TimeSheet\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TimeSheetWeekRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeSheetWeek::class);
    }

    /**
     * Récupère la feuille de temps sans créer
     *
     * @throws \DateMalformedStringException
     */
    public function findCurrentWeekForUser(User $user, string $weekStart): ?TimeSheetWeek
    {
        $startDate = new \DateTime($weekStart);
        $monday = TimeSheetWeek::getMondayOfWeek($startDate);

        return $this->createQueryBuilder('w')
            ->andWhere('w.user = :user')
            ->andWhere('w.startDate = :startDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $monday)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupère ou crée la feuille de temps (uniquement quand on ajoute une entry)
     *
     * @throws \DateMalformedStringException
     */
    public function findOrCreateWeekForUser(User $user, string $weekStart): TimeSheetWeek
    {
        $timesheet = $this->findCurrentWeekForUser($user, $weekStart);

        if (!$timesheet) {
            $startDate = new \DateTime($weekStart);
            $monday = TimeSheetWeek::getMondayOfWeek($startDate);

            $timesheet = new TimeSheetWeek();
            $timesheet->setUser($user);
            $timesheet->setStartDate($monday);

            $this->getEntityManager()->persist($timesheet);
            $this->getEntityManager()->flush();
        }

        return $timesheet;
    }

    /**
     * Récupèrer les timesheets avec filtres et pagination
     */
    public function findFiltered(Request $request): array
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, (int) $request->query->get('limit', 10));
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('tw')
            ->leftJoin('tw.user', 'u')
            ->addSelect('u')
            ->leftJoin('tw.timeSheetEntries', 'te')
            ->addSelect('te')
            ->leftJoin('te.client', 'c')
            ->addSelect('c')
            ->leftJoin('te.project', 'p')
            ->addSelect('p')
            ->andWhere('tw.status != :draft')
            ->setParameter('draft', Status::DRAFT);

        // Filtres
        if ($dateFrom = $request->query->get('dateFrom')) {
            try {
                $fromDate = new \DateTime($dateFrom);
                $qb->andWhere('tw.startDate >= :dateFrom')
                    ->setParameter('dateFrom', $fromDate);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Format de date "dateFrom" invalide: ' . $dateFrom);
            }
        }

        if ($dateTo = $request->query->get('dateTo')) {
            try {
                $toDate = new \DateTime($dateTo);
                $toDate->modify('+1 day');
                $qb->andWhere('tw.startDate < :dateTo')
                    ->setParameter('dateTo', $toDate);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Format de date "dateTo" invalide: ' . $dateTo);
            }
        }

        if ($userId = $request->query->get('userId')) {
            $qb->andWhere('tw.user = :userId')
                ->setParameter('userId', $userId);
        }

        if ($status = $request->query->get('status')) {
            try {
                $enumStatus = Status::from($status);
                $qb->andWhere('tw.status = :status')
                    ->setParameter('status', $enumStatus);
            } catch (\ValueError $e) {
                throw new \InvalidArgumentException('Status invalide: ' . $status);
            }
        }

        if ($clientId = $request->query->get('client')) {
            $qb->andWhere('c.id = :clientId')
                ->setParameter('clientId', $clientId);
        }

        $qb->orderBy('tw.startDate', 'DESC')
            ->addOrderBy('tw.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb, fetchJoinCollection: true);
        $total = count($paginator);

        return [
            'timesheets' => iterator_to_array($paginator),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int) ceil($total / $limit),
        ];
    }
}
