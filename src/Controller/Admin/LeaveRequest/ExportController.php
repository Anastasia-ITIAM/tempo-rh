<?php

namespace App\Controller\Admin\LeaveRequest;

use App\Repository\LeaveRequestRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ExportController extends AbstractController
{
    /**
     * @throws \DateMalformedStringException
     */
    #[Route('/export', name: 'export')]
    public function export(Request $request, LeaveRequestRepository $leaveRequestRepository, UserRepository $userRepository): Response
    {
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');

        $startDate = new \DateTimeImmutable($startDate);
        $endDate = new \DateTimeImmutable($endDate);

        $leaveRequests = $leaveRequestRepository->findRequestsBetween($startDate, $endDate);

        $results = [];

        foreach ($userRepository->findAll() as $user) {
            $fullName = $user->getFullName();

            $userRequests = array_filter(
                $leaveRequests,
                fn ($lr) => $lr->getRequestedBy()?->getId() === $user->getId()
            );

            if (empty($userRequests)) {
                $results[] = [
                    'employee' => $fullName,
                    'reason' => '-',
                    'start' => null,
                    'end' => null,
                    'leave_type' => '-',
                ];

                continue;
            }

            foreach ($userRequests as $leaveRequest) {
                $period = $leaveRequest->getPeriod();

                $results[] = [
                    'employee' => $fullName,
                    'reason' => $leaveRequest->getReason() ?? '-',
                    'start' => $period?->getStartedAt(),
                    'end' => $period?->getEndedAt(),
                    'leave_type' => sprintf(
                        'CP: %s | RTT: %s',
                        $leaveRequest->getCpDays() ?? '-',
                        $leaveRequest->getRtt() ?? '-'
                    ),                ];
            }
        }

        $bom = "\xEF\xBB\xBF";
        $csvContent = $bom . "Employé;Raison;Début;Fin;Type de congé\n";

        foreach ($results as $row) {
            $start = $row['start'] ? $row['start']->format('d/m/Y') : '-';
            $end = $row['end'] ? $row['end']->format('d/m/Y') : '-';

            $csvContent .= sprintf(
                "%s;%s;%s;%s;%s\n",
                $row['employee'],
                $row['reason'],
                $start,
                $end,
                $row['leave_type']
            );
        }

        return new Response(
            $csvContent,
            200,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="export_' . date('Y-m-d') . '.csv"',
            ]
        );
    }
}
