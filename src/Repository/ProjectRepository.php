<?php

namespace App\Repository;

use App\Entity\TimeSheet\Client;
use App\Entity\TimeSheet\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findByClient(Client $client): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.client', 'c')
            ->where('p.client = :client')
            ->andWhere('p.isActive = true')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('client', $client)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
