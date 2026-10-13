<?php

namespace App\Entity\TimeSheet;

use App\Entity\User;
use App\Enum\TimeSheet\Status;
use App\Repository\TimeSheetWeekRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TimeSheetWeekRepository::class)]
class TimeSheetWeek
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'timeSheetWeeks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $startDate = null;

    #[ORM\Column(enumType: Status::class)]
    private ?Status $status = Status::DRAFT;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $submittedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $reviewedAt = null;

    #[ORM\ManyToOne(inversedBy: 'reviewedTimeSheetWeeks')]
    private ?User $reviewedBy = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    /**
     * @var Collection<int, TimeSheetEntry>
     */
    #[ORM\OneToMany(targetEntity: TimeSheetEntry::class, mappedBy: 'timeSheetWeek', orphanRemoval: true)]
    private Collection $timeSheetEntries;

    /**
     * @var Collection<int, TimeSheetWeekHistory>
     */
    #[ORM\OneToMany(targetEntity: TimeSheetWeekHistory::class, mappedBy: 'timeSheetWeek', orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $history;

    public function __construct()
    {
        $this->timeSheetEntries = new ArrayCollection();
        $this->history = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
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

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): static
    {
        $this->startDate = $startDate;

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

    public function getSubmittedAt(): ?\DateTime
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(?\DateTime $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getReviewedAt(): ?\DateTime
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?\DateTime $reviewedAt): static
    {
        $this->reviewedAt = $reviewedAt;

        return $this;
    }

    public function getReviewedBy(): ?User
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(?User $reviewedBy): static
    {
        $this->reviewedBy = $reviewedBy;

        return $this;
    }

    /**
     * @return Collection<int, TimeSheetEntry>
     */
    public function getTimeSheetEntries(): Collection
    {
        return $this->timeSheetEntries;
    }

    public function addTimeSheetEntry(TimeSheetEntry $timeSheetEntry): static
    {
        if (!$this->timeSheetEntries->contains($timeSheetEntry)) {
            $this->timeSheetEntries->add($timeSheetEntry);
            $timeSheetEntry->setTimeSheetWeek($this);
        }

        return $this;
    }

    public function removeTimeSheetEntry(TimeSheetEntry $timeSheetEntry): static
    {
        if ($this->timeSheetEntries->removeElement($timeSheetEntry)) {
            // set the owning side to null (unless already changed)
            if ($timeSheetEntry->getTimeSheetWeek() === $this) {
                $timeSheetEntry->setTimeSheetWeek(null);
            }
        }

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    /**
     * @return Collection<int, TimeSheetWeekHistory>
     */
    public function getHistory(): Collection
    {
        return $this->history;
    }

    public function addHistory(TimeSheetWeekHistory $entry): static
    {
        if (!$this->history->contains($entry)) {
            $this->history->add($entry);
            $entry->setTimeSheetWeek($this);
        }

        return $this;
    }

    public function removeHistory(TimeSheetWeekHistory $entry): static
    {
        if ($this->history->removeElement($entry)) {
            if ($entry->getTimeSheetWeek() === $this) {
                $entry->setTimeSheetWeek(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->startDate->format('d/m/Y');
    }

    public static function getMondayOfWeek(\DateTime $date): \DateTime
    {
        $monday = clone $date;
        $dayOfWeek = (int) $monday->format('N');

        if ($dayOfWeek !== 1) {
            $monday->modify('-' . ($dayOfWeek - 1) . ' days');
        }

        return $monday->setTime(0, 0, 0);
    }

    public function getGroupedRows(): array
    {
        $grouped = [];

        foreach ($this->timeSheetEntries as $entry) {
            $key = $entry->getClient()->getId() . '-' . $entry->getProject()->getId() . '-' . $entry->getTask()->getId();

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'client' => $entry->getClient(),
                    'project' => $entry->getProject(),
                    'task' => $entry->getTask(),
                    'entriesByDate' => [],
                    'total' => 0,
                ];
            }

            $dateKey = $entry->getDate()->format('Y-m-d');
            $grouped[$key]['entriesByDate'][$dateKey] = $entry;
            $grouped[$key]['total'] += $entry->getHours();
        }

        return array_values($grouped);
    }

    public function getDailyTotals(): array
    {
        $totals = [];

        foreach ($this->timeSheetEntries as $entry) {
            $dateKey = $entry->getDate()->format('Y-m-d');

            if (!isset($totals[$dateKey])) {
                $totals[$dateKey] = 0;
            }

            $totals[$dateKey] += $entry->getHours();
        }

        return $totals;
    }
}
