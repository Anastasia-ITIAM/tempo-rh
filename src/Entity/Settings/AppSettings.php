<?php

namespace App\Entity\Settings;

use App\Repository\Settings\AppSettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[\AllowDynamicProperties]
#[ORM\Entity(repositoryClass: AppSettingsRepository::class)]
class AppSettings
{
    // Couleurs par défaut = couleurs actuelles de l'application
    public const string DEFAULT_PRIMARY = '#9344A3';
    public const string DEFAULT_SECONDARY = '#121328';
    public const string DEFAULT_TERTIARY = '#FF4495';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 7)]
    #[Assert\NotBlank]
    #[Assert\CssColor(formats: Assert\CssColor::HEX_LONG, message: 'Couleur invalide (format attendu : #RRGGBB).')]
    private string $primaryColor = self::DEFAULT_PRIMARY;

    #[ORM\Column(length: 7)]
    #[Assert\NotBlank]
    #[Assert\CssColor(formats: Assert\CssColor::HEX_LONG, message: 'Couleur invalide (format attendu : #RRGGBB).')]
    private string $secondaryColor = self::DEFAULT_SECONDARY;

    #[ORM\Column(length: 7)]
    #[Assert\NotBlank]
    #[Assert\CssColor(formats: Assert\CssColor::HEX_LONG, message: 'Couleur invalide (format attendu : #RRGGBB).')]
    private string $tertiaryColor = self::DEFAULT_TERTIARY;

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

    public function getTertiaryColor(): string
    {
        return $this->tertiaryColor;
    }

    public function setTertiaryColor(string $tertiaryColor): static
    {
        $this->tertiaryColor = $tertiaryColor;

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

    /** Remet les couleurs d'origine */
    public function resetColors(): static
    {
        $this->primaryColor = self::DEFAULT_PRIMARY;
        $this->secondaryColor = self::DEFAULT_SECONDARY;
        $this->tertiaryColor = self::DEFAULT_TERTIARY;

        return $this;
    }
}
