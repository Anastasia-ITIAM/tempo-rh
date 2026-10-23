<?php

namespace App\Service\Branding;

use App\Entity\AppSetting;
use App\Repository\AppSettingRepository;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Fournit le réglage de personnalisation et le logo, avec des valeurs par défaut.
 */
readonly class BrandingService
{
    public const LOGO_DIRECTORY = 'upload/branding';
    public const DEFAULT_LOGO_URL = '/images/logo-default.svg';

    public function __construct(
        private AppSettingRepository $repository,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    ) {
    }

    public function getSettings(): AppSetting
    {
        return $this->repository->getCurrent();
    }

    public function getLogoUrl(): string
    {
        $filename = $this->getSettings()->getLogoFilename();

        if (null === $filename || !is_file($this->projectDir.'/public/'.self::LOGO_DIRECTORY.'/'.$filename)) {
            return self::DEFAULT_LOGO_URL;
        }

        return '/'.self::LOGO_DIRECTORY.'/'.$filename;
    }

    public function getAbsoluteLogoDirectory(): string
    {
        return $this->projectDir.'/public/'.self::LOGO_DIRECTORY;
    }
}
