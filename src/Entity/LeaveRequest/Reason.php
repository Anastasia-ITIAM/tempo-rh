<?php

namespace App\Entity\LeaveRequest;

use App\Repository\ReasonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[\AllowDynamicProperties]
#[ORM\Entity(repositoryClass: ReasonRepository::class)]
class Reason
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(length: 255)]
    private string $label;

    #[ORM\OneToMany(targetEntity: LeaveRequest::class, mappedBy: 'reason')]
    private Collection $leaveRequest;

    public function __construct()
    {
        $this->leaveRequest = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getLeaveRequest(): Collection
    {
        return $this->leaveRequest;
    }

    public function setLeaveRequest(Collection $leaveRequest): void
    {
        $this->leaveRequest = $leaveRequest;
    }

    public function __toString(): string
    {
        return $this->label;
    }
}
