<?php

namespace App\Entity;

use App\Entity\Traits\CustomTimestampableWithoutDeleted;
use App\Enum\Notification\NotificationFlag;
use App\Enum\Notification\NotificationStatus;
use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Notification
{
    use CustomTimestampableWithoutDeleted;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $recipient = null;

    #[ORM\Column(enumType: NotificationFlag::class)]
    private ?NotificationFlag $flag = null;

    #[ORM\Column(enumType: NotificationStatus::class)]
    private ?NotificationStatus $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error = null;

    #[ORM\Column(nullable: true)]
    private ?array $context = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function setRecipient(string $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getFlag(): ?NotificationFlag
    {
        return $this->flag;
    }

    public function setFlag(NotificationFlag $flag): static
    {
        $this->flag = $flag;

        return $this;
    }

    public function getStatus(): ?NotificationStatus
    {
        return $this->status;
    }

    public function setStatus(NotificationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): static
    {
        $this->error = $error;

        return $this;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function setContext(?array $context): static
    {
        $this->context = $context;

        return $this;
    }
}
