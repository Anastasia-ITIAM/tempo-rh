<?php

namespace App\Entity\TimeSheet;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[Gedmo\SoftDeleteable(fieldName: "deletedAt")]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $isActive = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'client', orphanRemoval: true)]
    private Collection $projects;

    /**
     * @var Collection<int, TimeSheetEntry>
     */
    #[ORM\OneToMany(targetEntity: TimeSheetEntry::class, mappedBy: 'client')]
    private Collection $timeSheetEntries;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
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
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setClient($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getClient() === $this) {
                $project->setClient(null);
            }
        }

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
            $timeSheetEntry->setClient($this);
        }

        return $this;
    }

    public function removeTimeSheetEntry(TimeSheetEntry $timeSheetEntry): static
    {
        if ($this->timeSheetEntries->removeElement($timeSheetEntry)) {
            // set the owning side to null (unless already changed)
            if ($timeSheetEntry->getClient() === $this) {
                $timeSheetEntry->setClient(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
