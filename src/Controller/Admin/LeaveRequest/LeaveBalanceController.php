<?php

namespace App\Controller\Admin\LeaveRequest;

use App\Entity\User;
use App\Enum\LeaveRequest\LeaveTypeCode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LeaveBalanceController extends AbstractController
{
    #[Route('/admin/leave/balance/{id}', name: 'admin_leave_balance')]
    #[IsGranted('ROLE_ADMIN')]
    public function balance(User $user): JsonResponse
    {
        $cp = 0;
        $rtt = 0;


        foreach ($user->getLeaveTypes() as $type) {

            if ($type->isExpired()) {
                continue;
            }

            match ($type->getCode()) {
                LeaveTypeCode::CP => $cp = $type->getCurrentNumber(),
                LeaveTypeCode::RTT => $rtt = $type->getCurrentNumber(),
                default => null,
            };
        }

        return $this->json([
            'cp' => $cp,
            'rtt' => $rtt,
        ]);
    }
}
