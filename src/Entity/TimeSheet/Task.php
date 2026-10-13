<?php

namespace App\Entity\TimeSheet;

use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: "deletedAt")]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le projet est obligatoire.')]
    private ?Project $project = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    /**
     * @var Collection<int, TimeSheetEntry>
     */
    #[ORM\OneToMany(targetEntity: TimeSheetEntry::class, mappedBy: 'task')]
    private Collection $timeSheetEntries;

    public function __construct()
    {
        $this->timeSheetEntries = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

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
            $timeSheetEntry->setTask($this);
        }

        return $this;
    }

    public function removeTimeSheetEntry(TimeSheetEntry $timeSheetEntry): static
    {
        if ($this->timeSheetEntries->removeElement($timeSheetEntry)) {
            // set the owning side to null (unless already changed)
            if ($timeSheetEntry->getTask() === $this) {
                $timeSheetEntry->setTask(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getClient(): ?Client
    {
        return $this->project?->getClient();
    }
}
