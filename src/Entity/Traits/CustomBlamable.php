<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait CustomBlamable
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $createdBy = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $updatedBy = null;

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?string $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?string $userName): self
    {
        $this->updatedBy = $userName;

        return $this;
    }
}
