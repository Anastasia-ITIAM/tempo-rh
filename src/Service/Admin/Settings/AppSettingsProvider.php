<?php

namespace App\Service\Admin\Settings;

use App\Entity\Settings\AppSettings;
use App\Repository\Settings\AppSettingsRepository;
use Symfony\Component\Asset\Packages;

class AppSettingsProvider
{
    public const DEFAULT_LOGO = 'images/logo-default.svg';

    private ?AppSettings $settings = null;

    public function __construct(
        private readonly AppSettingsRepository $settingsRepository,
        private readonly Packages $packages,
    ) {
    }

    /** Lus une seule fois par requête */
    public function getSettings(): AppSettings
    {
        return $this->settings ??= $this->settingsRepository->getSettings();
    }

    /** URL du logo personnalisé, ou du logo par défaut */
    public function getLogoUrl(): string
    {
        $filename = $this->getSettings()->getLogoFilename();

        return $this->packages->getUrl(
            $filename ? 'upload/settings/' . $filename : self::DEFAULT_LOGO
        );
    }
}
