<?php

namespace App\Service\Admin\LeaveRequest;

use App\Entity\LeaveRequest\LeaveType;
use App\Repository\LeaveTypeRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class LeaveTypeRenewalService
{
    public function __construct(
        private EntityManagerInterface $em,
        private LeaveTypeRepository $leaveTypeRepository,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function renewIfExpired(): void
    {
        $today = new \DateTimeImmutable('today');

        // Appelé à chaque ouverture du tableau de bord : on ne charge que les lignes à renouveler,
        // filtrées par la base (index sur archived_at, end_date), au lieu de tous les types de congés.
        $leaveTypes = $this->leaveTypeRepository->findExpiredToRenew($today);

        if ([] === $leaveTypes) {
            return;
        }

        foreach ($leaveTypes as $leaveType) {
            $leaveType->setArchivedAt(new \DateTimeImmutable());

            $new = new LeaveType();
            $new->setUser($leaveType->getUser());
            $new->setCode($leaveType->getCode());
            $new->setMaxInitialNumber($leaveType->getMaxInitialNumber());
            $new->setCurrentNumber($leaveType->getMaxInitialNumber());

            $newStart = $leaveType->getEndDate()->modify('+1 day');
            $newEnd = (clone $newStart)->modify('+1 year')->modify('-1 day');

            $new->setStartDate($newStart);
            $new->setEndDate($newEnd);

            $this->em->persist($leaveType);
            $this->em->persist($new);
        }

        $this->em->flush();
    }
}
