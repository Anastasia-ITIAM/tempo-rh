<?php

namespace App\Entity;

use App\Entity\ExpenseReport\ExpenseReport;
use App\Entity\LeaveRequest\LeaveRequest;
use App\Entity\LeaveRequest\LeaveType;
use App\Entity\TimeSheet\TimeSheetWeek;
use App\Entity\Traits\CustomBlamable;
use App\Entity\Traits\CustomTimestampable;
use App\Enum\LeaveRequest\LeaveTypeCode;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[Gedmo\SoftDeleteable(fieldName: "deletedAt")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use CustomBlamable;
    use CustomTimestampable;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 250)]
    private ?string $lastName = null;

    #[ORM\Column(length: 250)]
    private ?string $firstName = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = ['ROLE_USER'];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    private ?string $plainPassword = null;

    /**
     * @var Collection<int, LeaveRequest>
     */
    #[ORM\OneToMany(targetEntity: LeaveRequest::class, mappedBy: 'requestedBy')]
    private Collection $leaveRequest;

    /**
     * @var Collection<int, ExpenseReport\>
     */
    #[ORM\OneToMany(targetEntity: ExpenseReport::class, mappedBy: 'requestedBy')]
    private Collection $expenseReport;

    /**
     * @var Collection<int, LeaveType>
     */
    #[ORM\OneToMany(targetEntity: LeaveType::class, mappedBy: 'user')]
    private Collection $leaveTypes;

    /**
     * @var Collection<int, TimeSheetWeek>
     */
    #[ORM\OneToMany(targetEntity: TimeSheetWeek::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $timeSheetWeeks;

    /**
     * @var Collection<int, TimeSheetWeek>
     */
    #[ORM\OneToMany(targetEntity: TimeSheetWeek::class, mappedBy: 'reviewedBy')]
    private Collection $reviewedTimeSheetWeeks;

    public function __construct()
    {
        $this->leaveRequest = new ArrayCollection();
        $this->expenseReport = new ArrayCollection();
        $this->leaveTypes = new ArrayCollection();
        $this->timeSheetWeeks = new ArrayCollection();
        $this->reviewedTimeSheetWeeks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getFullName(): string
    {
        return mb_trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    /**
     * @return Collection<int, LeaveRequest>
     */
    public function getLeaveRequest(): Collection
    {
        return $this->leaveRequest;
    }

    public function addLeaveRequest(LeaveRequest $leaveRequest): static
    {
        if (!$this->leaveRequest->contains($leaveRequest)) {
            $this->leaveRequest->add($leaveRequest);
            $leaveRequest->setRequestedBy($this);
        }

        return $this;
    }

    public function removeLeaveRequest(LeaveRequest $leaveRequest): static
    {
        if ($this->leaveRequest->removeElement($leaveRequest)) {
            // set the owning side to null (unless already changed)
            if ($leaveRequest->getRequestedBy() === $this) {
                $leaveRequest->setRequestedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ExpenseReport>
     */
    public function getExpenseReport(): Collection
    {
        return $this->expenseReport;
    }

    public function addExpenseReport(ExpenseReport $expenseReport): static
    {
        if (!$this->expenseReport->contains($expenseReport)) {
            $this->expenseReport->add($expenseReport);
            $expenseReport->setRequestedBy($this);
        }

        return $this;
    }

    public function removeExpenseReport(ExpenseReport $expenseReport): static
    {
        if ($this->expenseReport->removeElement($expenseReport)) {
            // set the owning side to null (unless already changed)
            if ($expenseReport->getRequestedBy() === $this) {
                $expenseReport->setRequestedBy(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getFullName() ?: 'Utilisateur inconnu';
    }

    /**
     * @return Collection<int, LeaveType>
     */
    public function getLeaveTypes(): Collection
    {
        return $this->leaveTypes;
    }

    public function addLeaveType(LeaveType $leaveType): static
    {
        if (!$this->leaveTypes->contains($leaveType)) {
            $this->leaveTypes->add($leaveType);
            $leaveType->setUser($this);
        }

        return $this;
    }

    public function removeLeaveType(LeaveType $leaveType): static
    {
        if ($this->leaveTypes->removeElement($leaveType)) {
            // set the owning side to null (unless already changed)
            if ($leaveType->getUser() === $this) {
                $leaveType->setUser(null);
            }
        }

        return $this;
    }

    public function getCpRemaining(): float
    {
        foreach ($this->leaveTypes as $leaveType) {
            if ($leaveType->getCode() === LeaveTypeCode::CP) {
                return $leaveType->getCurrentNumber();
            }
        }

        return 0;
    }

    public function getRttRemaining(): float
    {
        $today = new \DateTimeImmutable('today');

        foreach ($this->leaveTypes as $leaveType) {

            if ($leaveType->getCode() !== LeaveTypeCode::RTT) {
                continue;
            }

            if ($leaveType->getArchivedAt() !== null) {
                continue;
            }

            if ($leaveType->getStartDate() > $today || $leaveType->getEndDate() < $today) {
                continue;
            }

            return $leaveType->getCurrentNumber();
        }

        return 0;
    }

    /**
     * @return Collection<int, TimeSheetWeek>
     */
    public function getTimeSheetWeeks(): Collection
    {
        return $this->timeSheetWeeks;
    }

    public function addTimeSheetWeek(TimeSheetWeek $timeSheetWeek): static
    {
        if (!$this->timeSheetWeeks->contains($timeSheetWeek)) {
            $this->timeSheetWeeks->add($timeSheetWeek);
            $timeSheetWeek->setUser($this);
        }

        return $this;
    }

    public function removeTimeSheetWeek(TimeSheetWeek $timeSheetWeek): static
    {
        if ($this->timeSheetWeeks->removeElement($timeSheetWeek)) {
            // set the owning side to null (unless already changed)
            if ($timeSheetWeek->getUser() === $this) {
                $timeSheetWeek->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TimeSheetWeek>
     */
    public function getReviewedTimeSheetWeeks(): Collection
    {
        return $this->reviewedTimeSheetWeeks;
    }

    public function addReviewedTimeSheetWeek(TimeSheetWeek $week): static
    {
        if (!$this->reviewedTimeSheetWeeks->contains($week)) {
            $this->reviewedTimeSheetWeeks->add($week);
            $week->setReviewedBy($this);
        }

        return $this;
    }

    public function removeReviewedTimeSheetWeek(TimeSheetWeek $week): static
    {
        if ($this->reviewedTimeSheetWeeks->removeElement($week)) {
            if ($week->getReviewedBy() === $this) {
                $week->setReviewedBy(null);
            }
        }

        return $this;
    }
}
