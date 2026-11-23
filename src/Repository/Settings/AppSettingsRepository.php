<?php

namespace App\Repository\Settings;

use App\Entity\Settings\AppSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AppSettings>
 */
class AppSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppSettings::class);
    }

    /**
     * Retourne LA ligne de réglages.
     * Si elle n'existe pas encore, elle est créée avec les couleurs par défaut.
     */
    public function getSettings(): AppSettings
    {
        $settings = $this->findOneBy([], ['id' => 'ASC']);

        if ($settings === null) {
            $settings = new AppSettings();
            $this->getEntityManager()->persist($settings);
            $this->getEntityManager()->flush();
        }

        return $settings;
    }
}
