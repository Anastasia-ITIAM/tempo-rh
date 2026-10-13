<?php

namespace App\Entity\TimeSheet;

use App\Entity\User;
use App\Enum\TimeSheet\Status;
use App\Repository\TimeSheetWeekHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TimeSheetWeekHistoryRepository::class)]
class TimeSheetWeekHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TimeSheetWeek::class, inversedBy: 'history')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TimeSheetWeek $timeSheetWeek = null;

    #[ORM\Column(enumType: Status::class)]
    private ?Status $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $actionBy = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTimeSheetWeek(): ?TimeSheetWeek
    {
        return $this->timeSheetWeek;
    }

    public function setTimeSheetWeek(?TimeSheetWeek $timeSheetWeek): static
    {
        $this->timeSheetWeek = $timeSheetWeek;

        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getActionBy(): ?User
    {
        return $this->actionBy;
    }

    public function setActionBy(User $actionBy): static
    {
        $this->actionBy = $actionBy;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
