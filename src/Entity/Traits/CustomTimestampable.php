<?php

namespace App\Entity\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

// add #[ORM\HasLifecycleCallbacks] to all entity that use this trait
#[ORM\HasLifecycleCallbacks]
trait CustomTimestampable
{
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, nullable: false)]
    protected ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, nullable: false)]
    protected ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(name: 'deleted_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $deletedAt = null;

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): self
    {
        if ($this->createdAt === null) {
            $newDate = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $this->createdAt = $newDate;
        }
        $this->updatedAt = $this->getCreatedAt();

        return $this;
    }

    public function setCreatedAtValue(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAt(): self
    {
        $newDate = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $this->updatedAt = $newDate;

        return $this;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedAt($deletedAt)
    {
        $newDate = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $this->deletedAt = $newDate;

        return $this;
    }
}
