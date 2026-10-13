<?php

namespace App\Entity\ExpenseReport;

use App\Entity\Traits\CustomBlamable;
use App\Entity\Traits\CustomTimestampableWithoutDeleted;
use App\Entity\User;
use App\Enum\ExpenseReport\Status;
use App\Repository\ExpenseReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: ExpenseReportRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class ExpenseReport
{
    use CustomBlamable;
    use CustomTimestampableWithoutDeleted;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $billedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $refundedAt = null;

    #[ORM\ManyToOne(inversedBy: 'expenseReport')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $requestedBy = null;

    #[ORM\Column(enumType: Status::class)]
    private ?Status $status = Status::PENDING;

    #[ORM\Column]
    private ?int $amount = null;

    #[Vich\UploadableField(mapping: 'report_file', fileNameProperty: 'path')]
    private ?File $reportFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $path = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getBilledAt(): ?\DateTimeImmutable
    {
        return $this->billedAt;
    }

    public function setBilledAt(?\DateTimeImmutable $billedAt): static
    {
        $this->billedAt = $billedAt;

        return $this;
    }

    public function getRefundedAt(): ?\DateTimeImmutable
    {
        return $this->refundedAt;
    }

    public function setRefundedAt(\DateTimeImmutable $refundedAt): static
    {
        $this->refundedAt = $refundedAt;

        return $this;
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

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getReportFile(): ?File
    {
        return $this->reportFile;
    }

    public function setReportFile(?File $file = null): void
    {
        $this->reportFile = $file;
        if ($file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): static
    {
        $this->path = $path;

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

    public function getStatusLabel(): string
    {
        return $this->status?->label() ?? '';
    }

    public function getStatusType(): string
    {
        return $this->status?->type() ?? '';
    }
}
