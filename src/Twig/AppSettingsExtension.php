<?php

namespace App\Twig;

use App\Service\Admin\Settings\AppSettingsProvider;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppSettingsExtension extends AbstractExtension
{
    public function __construct(
        private readonly AppSettingsProvider $provider
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_settings', [$this->provider, 'getSettings']),
            new TwigFunction('app_logo', [$this->provider, 'getLogoUrl']),
        ];
    }
}
