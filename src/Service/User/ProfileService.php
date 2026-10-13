<?php

namespace App\Service\User;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class ProfileService
{
    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher,
        private Security $security,
    ) {
    }

    public function setPassword(User $entityInstance): void
    {
        if (!($plainPassword = $entityInstance->getPlainPassword())) {
            return;
        }

        $entityInstance->setPassword(
            $this->userPasswordHasher->hashPassword($entityInstance, $plainPassword)
        );

        $entityInstance->setPlainPassword(null);
    }

    public function setBlamable(User $entityInstance, bool $isNew): void
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
