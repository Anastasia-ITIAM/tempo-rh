<?php

namespace App\Service\Admin\LeaveRequest;

use App\Entity\LeaveRequest\LeaveRequest;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

readonly class BlamableService
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function setBlamable(LeaveRequest $entityInstance, bool $isNew): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $fullName = $user?->getFullName();

        if ($isNew) {
            $entityInstance->setCreatedBy($fullName);

            return;
        }
        $entityInstance->setUpdatedBy($fullName);
        
    }
}
