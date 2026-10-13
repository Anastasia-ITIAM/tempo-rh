<?php

namespace App\Entity\LeaveRequest;

use App\Entity\User;
use App\Enum\LeaveRequest\LeaveTypeCode;
use App\Repository\LeaveTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LeaveTypeRepository::class)]
class LeaveType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'leaveTypes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(enumType: LeaveTypeCode::class)]
    private LeaveTypeCode $code;
    #[ORM\Column]
    private int $maxInitialNumber = 0;
    #[ORM\Column]
    private float $currentNumber = 0;
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?\DateTimeImmutable $archivedAt): void
    {
        $this->archivedAt = $archivedAt;
    }


    /**
     * @var Collection<int, LeaveRequest>
     */
    #[ORM\OneToMany(targetEntity: LeaveRequest::class, mappedBy: 'leaveType')]
    private Collection $leaveRequests;

    public function __construct()
    {
        $this->leaveRequests = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCode(): LeaveTypeCode
    {
        return $this->code;
    }

    public function setCode(LeaveTypeCode $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getMaxInitialNumber(): ?int
    {
        return $this->maxInitialNumber;
    }

    public function setMaxInitialNumber(int $maxInitialNumber): static
    {
        $this->maxInitialNumber = $maxInitialNumber;

        return $this;
    }

    public function getCurrentNumber(): float
    {
        return $this->currentNumber;
    }

    public function setCurrentNumber(float $currentNumber): static
    {
        $this->currentNumber = $currentNumber;

        return $this;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * @return Collection<int, LeaveRequest>
     */
    public function getLeaveRequests(): Collection
    {
        return $this->leaveRequests;
    }

    public function addLeaveRequest(LeaveRequest $leaveRequest): static
    {
        if (!$this->leaveRequests->contains($leaveRequest)) {
            $this->leaveRequests->add($leaveRequest);
            $leaveRequest->setLeaveType($this);
        }

        return $this;
    }

    public function removeLeaveRequest(LeaveRequest $leaveRequest): static
    {
        if ($this->leaveRequests->removeElement($leaveRequest)) {
            // set the owning side to null (unless already changed)
            if ($leaveRequest->getLeaveType() === $this) {
                $leaveRequest->setLeaveType(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return match ($this->code) {
            LeaveTypeCode::CP => 'CP',
            LeaveTypeCode::RTT => 'RTT',
        };
    }

    public function isExpired(): bool
    {
        if (!$this->endDate) {
            return false;
        }

        $today = new \DateTimeImmutable('today');

        return $this->endDate < $today;
    }
}
