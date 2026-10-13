<?php

namespace App\Service\Admin\LeaveRequest;

use App\Entity\LeaveRequest\LeaveType;
use Doctrine\ORM\EntityManagerInterface;

readonly class LeaveTypeRenewalService
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function renewIfExpired(): void
    {
        $today = new \DateTimeImmutable('today');

        $leaveTypes = $this->em->getRepository(LeaveType::class)->findAll();

        foreach ($leaveTypes as $leaveType) {

            if ($leaveType->getArchivedAt() || $leaveType->getEndDate() > $today) {
                continue;
            }

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
