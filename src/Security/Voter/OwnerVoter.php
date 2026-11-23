<?php

namespace App\Security\Voter;

use App\Entity\ExpenseReport\ExpenseReport;
use App\Entity\LeaveRequest\LeaveRequest;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Accès réservé à l'employé concerné par la demande (congé ou note de frais).
 *
 * Usage : $this→denyAccessUnlessGranted(OwnerVoter: OWNER, $leaveRequest)
 *         ou Crud::setEntityPermission(OwnerVoter:OWNER) dans EasyAdmin.
 */
final class OwnerVoter extends Voter
{
    public const string OWNER = 'OWNER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::OWNER
            && ($subject instanceof LeaveRequest || $subject instanceof ExpenseReport);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var LeaveRequest|ExpenseReport $subject */
        return $subject->getRequestedBy()?->getId() === $user->getId();
    }
}
