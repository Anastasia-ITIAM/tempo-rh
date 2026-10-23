<?php

namespace App\Repository;

use App\Entity\AppSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppSetting>
 */
class AppSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppSetting::class);
    }

    /**
     * Retourne la ligne de réglages, ou une instance par défaut non persistée si elle n'existe pas encore.
     */
    public function getCurrent(): AppSetting
    {
        return $this->findOneBy([]) ?? new AppSetting();
    }
}
