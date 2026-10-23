<?php

namespace App\Twig;

use App\Entity\AppSetting;
use App\Service\Branding\BrandingService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BrandingExtension extends AbstractExtension
{
    private ?AppSetting $settings = null;

    public function __construct(private readonly BrandingService $branding)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_settings', [$this, 'getSettings']),
            new TwigFunction('branding_logo_url', fn (): string => $this->branding->getLogoUrl()),
        ];
    }

    public function getSettings(): AppSetting
    {
        return $this->settings ??= $this->branding->getSettings();
    }
}
