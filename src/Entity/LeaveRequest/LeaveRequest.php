<?php

namespace App\Entity\LeaveRequest;

use App\Entity\Traits\CustomBlamable;
use App\Entity\Traits\CustomTimestampableWithoutDeleted;
use App\Entity\User;
use App\Enum\LeaveRequest\Status;
use App\Repository\LeaveRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LeaveRequestRepository::class)]
#[ORM\HasLifecycleCallbacks]
class LeaveRequest
{
    use CustomBlamable;
    use CustomTimestampableWithoutDeleted;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'leaveRequest')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $requestedBy = null;

    #[ORM\Column(enumType: Status::class)]
    private ?Status $status = Status::PENDING;

    #[ORM\OneToOne(targetEntity: Period::class, mappedBy: 'leaveRequest', cascade: ['persist', 'remove'])]
    private ?Period $period = null;

    #[ORM\ManyToOne(inversedBy: 'leaveRequest')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reason $reason = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $rtt = null;

    #[ORM\ManyToOne(inversedBy: 'leaveRequests')]
    #[ORM\JoinColumn(nullable: true)]
    private ?LeaveType $leaveType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentAdmin = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentUser = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestedBy(): ?User
    {
        return $this->requestedBy;
    }

    public function setRequestedBy(?User $requestedBy): static
    {
        $this->requestedBy = $requestedBy;

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

    public function getPeriod(): ?Period
    {
        return $this->period;
    }

    public function setPeriod(?Period $period): static
    {
        $this->period = $period;
        if ($period && $period->getLeaveRequest() !== $this) {
            $period->setLeaveRequest($this);
        }

        return $this;
    }

    public function getReason(): ?Reason
    {
        return $this->reason;
    }

    public function setReason(?Reason $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getRtt(): ?float
    {
        return $this->rtt;
    }

    public function setRtt(?float $rtt): static
    {
        $this->rtt = $rtt;

        return $this;
    }

    public function getCommentAdmin(): ?string
    {
        return $this->commentAdmin;
    }

    public function setCommentAdmin(?string $commentAdmin): static
    {
        $this->commentAdmin = $commentAdmin;

        return $this;
    }

    public function getCommentUser(): ?string
    {
        return $this->commentUser;
    }

    public function setCommentUser(?string $commentUser): void
    {
        $this->commentUser = $commentUser;
    }

    public function getStatusLabel(): string
    {
        return $this->status?->label() ?? '';
    }

    public function getStatusType(): string
    {
        return $this->status?->type() ?? '';
    }

    public function getLeaveType(): ?LeaveType
    {
        return $this->leaveType;
    }

    public function setLeaveType(?LeaveType $leaveType): static
    {
        $this->leaveType = $leaveType;

        return $this;
    }

    public function getNumberOfDays(): float
    {
        return $this->period?->getDuration() ?? 0;
    }

    public function isVacation(): bool
    {
        return $this->getReason()?->getType() === 'vacation';
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function getCpDays(): float
    {
        if ($this->getReason()?->getType() !== 'vacation') {
            return 0;
        }

        $days = $this->getPeriod()?->getDuration() ?? 0;
        $rtt = (float) ($this->getRtt() ?? 0);

        return max(0, $days - $rtt);
    }
}
