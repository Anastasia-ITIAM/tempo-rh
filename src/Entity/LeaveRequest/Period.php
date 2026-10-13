<?php

namespace App\Entity\LeaveRequest;

use App\Repository\PeriodRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PeriodRepository::class)]
class Period
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\OneToOne(targetEntity: LeaveRequest::class, inversedBy: 'period', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?LeaveRequest $leaveRequest = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(\DateTimeImmutable $endedAt): static
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getLeaveRequest(): ?LeaveRequest
    {
        return $this->leaveRequest;
    }

    public function setLeaveRequest(?LeaveRequest $leaveRequest): static
    {
        $this->leaveRequest = $leaveRequest;

        if ($leaveRequest && $leaveRequest->getPeriod() !== $this) {
            $leaveRequest->setPeriod($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        if ($this->startedAt && $this->endedAt) {
            return $this->startedAt->format('d/m/Y') . ' - ' . $this->endedAt->format('d/m/Y');
        }

        return '';
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function getDuration(): float
    {
        if (!$this->startedAt || !$this->endedAt) {
            return 0;
        }

        $days = 0.0;
        $current = $this->startedAt->setTime(0, 0);
        $endDate = $this->endedAt->setTime(0, 0);

        // On boucle sur chaque jour de la période pour ignorer les samedis/dimanches et détecter les demi-journées
        while ($current <= $endDate) {
            // N = 1 (Lundi) à 7 (Dimanche)
            $dayOfWeek = (int) $current->format('N');

            if ($dayOfWeek <= 5) { // Semaine (Lundi-Vendredi)
                $dayValue = 1.0;

                if ($current->format('Y-m-d') === $this->startedAt->format('Y-m-d')) {
                    if ($this->startedAt->format('H:i') === '12:00') {
                        $dayValue -= 0.5;
                    }
                }

                if ($current->format('Y-m-d') === $this->endedAt->format('Y-m-d')) {
                    if ($this->endedAt->format('H:i') === '08:00') {
                        $dayValue -= 1.0;
                    } elseif ($this->endedAt->format('H:i') === '12:00') {
                        $dayValue -= 0.5;
                    }
                }

                $days += max(0, $dayValue);
            }

            $current = $current->modify('+1 day');
        }

        return $days;
    }
}
