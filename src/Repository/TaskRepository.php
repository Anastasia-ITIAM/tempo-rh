<?php

namespace App\Repository;

use App\Entity\TimeSheet\Project;
use App\Entity\TimeSheet\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.project', 'p')
            ->innerJoin('p.client', 'c')
            ->where('t.project = :project')
            ->andWhere('t.isActive = true')
            ->andWhere('c.deletedAt IS NULL')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('project', $project)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
