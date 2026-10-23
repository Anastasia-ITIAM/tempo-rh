<?php

namespace App\Entity;

use App\Repository\AppSettingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Réglages de personnalisation de l'application (couleurs et logo).
 * Une seule ligne existe en base : les réglages sont lus via AppSettingRepository::getCurrent().
 */
#[ORM\Entity(repositoryClass: AppSettingRepository::class)]
#[ORM\Table(name: 'app_setting')]
class AppSetting
{
    public const DEFAULT_PRIMARY = '#9344A3';
    public const DEFAULT_SECONDARY = '#121328';
    public const DEFAULT_ACCENT = '#FF4495';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 7)]
    private string $primaryColor = self::DEFAULT_PRIMARY;

    #[ORM\Column(length: 7)]
    private string $secondaryColor = self::DEFAULT_SECONDARY;

    #[ORM\Column(length: 7)]
    private string $accentColor = self::DEFAULT_ACCENT;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoFilename = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrimaryColor(): string
    {
        return $this->primaryColor;
    }

    public function setPrimaryColor(string $primaryColor): static
    {
        $this->primaryColor = $primaryColor;

        return $this;
    }

    public function getSecondaryColor(): string
    {
        return $this->secondaryColor;
    }

    public function setSecondaryColor(string $secondaryColor): static
    {
        $this->secondaryColor = $secondaryColor;

        return $this;
    }

    public function getAccentColor(): string
    {
        return $this->accentColor;
    }

    public function setAccentColor(string $accentColor): static
    {
        $this->accentColor = $accentColor;

        return $this;
    }

    public function getLogoFilename(): ?string
    {
        return $this->logoFilename;
    }

    public function setLogoFilename(?string $logoFilename): static
    {
        $this->logoFilename = $logoFilename;

        return $this;
    }

    public function resetColors(): static
    {
        $this->primaryColor = self::DEFAULT_PRIMARY;
        $this->secondaryColor = self::DEFAULT_SECONDARY;
        $this->accentColor = self::DEFAULT_ACCENT;

        return $this;
    }
}
