<?php

namespace App\Controller\Admin\TimeSheet\Api;

use App\Entity\TimeSheet\TimeSheetWeek;
use App\Entity\TimeSheet\TimeSheetWeekHistory;
use App\Entity\User;
use App\Enum\Notification\NotificationFlag;
use App\Enum\TimeSheet\Status;
use App\Repository\TimeSheetEntryRepository;
use App\Repository\TimeSheetWeekRepository;
use App\Repository\UserRepository;
use App\Service\Admin\Notification\NotificationService;
use App\Service\Admin\TimeSheet\ExportTimeSheetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/api/admin/timesheet')]
class ApiAdminTimeSheetController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TimeSheetWeekRepository $timesheetRepo,
        private readonly ExportTimeSheetService $exportTimeSheetService,
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
    ) {
        $this->em->getFilters()->disable('softdeleteable');
    }

    /**
     * Lister les timesheets avec filtres et pagination
     */
    #[Route('/list', name: 'api_timesheet_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $result = $this->timesheetRepo->findFiltered($request);

            $data = array_map(
                fn (TimeSheetWeek $ts) => $this->formatTimesheet($ts),
                $result['timesheets']
            );

            return $this->json([
                'timesheets' => $data,
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'totalPages' => $result['totalPages'],
            ]);

        } catch (\InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['message' => 'Erreur: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Approuver une feuille de temps
     */
    #[Route('/{id}/approve', name: 'api_timesheet_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function approve(TimeSheetWeek $timesheet, Request $request): JsonResponse
    {
        try {
            if ($timesheet->getStatus() !== Status::SUBMITTED) {
                return $this->json(
                    ['message' => 'Cette feuille de temps ne peut pas être approuvée (statut: ' . $timesheet->getStatus()->value . ')'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            /** @var User $admin */
            $admin = $this->getUser();

            $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));

            $timesheet->setStatus(Status::APPROVED);
            $timesheet->setReviewedAt($now);
            $timesheet->setReviewedBy($admin);
            $timesheet->setComment(null);

            $history = new TimeSheetWeekHistory()
                ->setTimeSheetWeek($timesheet)
                ->setStatus(Status::APPROVED)
                ->setActionBy($admin)
                ->setCreatedAt($now);

            $this->em->persist($history);
            $this->em->flush();

            $admins = $this->userRepository->findByRole('ROLE_ADMIN');

            foreach ($admins as $admin) {
                $this->notificationService->sendNotification(
                    $admin->getEmail(),
                    NotificationFlag::TimeSheetWeekApprovedByAdminToAdmin,
                    [
                        'employee' => $timesheet->getUser()->getFullName(),
                    ]
                );
            }

            $this->notificationService->sendNotification(
                $timesheet->getUser()->getEmail(),
                NotificationFlag::TimeSheetWeekApprovedByAdminToUser,
                [
                    'employeeName' => $timesheet->getUser()->getFirstName(),
                ]
            );

            return $this->json([
                'success' => true,
                'message' => 'Feuille de temps approuvée',
                'timesheet' => $this->formatTimesheet($timesheet),
            ]);

        } catch (\Exception $e) {
            return $this->json(
                ['message' => 'Erreur: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Rejeter une feuille de temps
     */
    #[Route('/{id}/reject', name: 'api_timesheet_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reject(TimeSheetWeek $timesheet, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $reason = $data['reason'] ?? '';

            if (!$reason || !is_string($reason)) {
                return $this->json(
                    ['message' => 'Une raison de rejet valide est requise'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            if ($timesheet->getStatus() !== Status::SUBMITTED) {
                return $this->json(
                    ['message' => 'Cette feuille de temps ne peut pas être rejetée'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            /** @var User $admin */
            $admin = $this->getUser();

            $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));

            $timesheet->setStatus(Status::REJECTED);
            $timesheet->setReviewedAt($now);
            $timesheet->setReviewedBy($admin);
            $timesheet->setComment($reason);

            $history = new TimeSheetWeekHistory()
                ->setTimeSheetWeek($timesheet)
                ->setStatus(Status::REJECTED)
                ->setComment($reason)
                ->setActionBy($admin)
                ->setCreatedAt($now);

            $this->em->persist($history);
            $this->em->flush();

            $admins = $this->userRepository->findByRole('ROLE_ADMIN');

            foreach ($admins as $admin) {
                $this->notificationService->sendNotification(
                    $admin->getEmail(),
                    NotificationFlag::TimeSheetWeekRejectedByAdminToAdmin,
                    [
                        'employee' => $timesheet->getUser()->getFullName(),
                    ]
                );
            }

            $this->notificationService->sendNotification(
                $timesheet->getUser()->getEmail(),
                NotificationFlag::TimeSheetWeekRejectedByAdminToUser,
                [
                    'employeeName' => $timesheet->getUser()->getFirstName(),
                    'adminComment' => $timesheet->getComment(),
                ]
            );

            return $this->json([
                'success' => true,
                'message' => 'Feuille de temps rejetée',
                'timesheet' => $this->formatTimesheet($timesheet),
            ]);

        } catch (\Exception $e) {
            return $this->json(
                ['message' => 'Erreur: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Formater une feuille de temps pour JSON
     */
    private function formatTimesheet(TimeSheetWeek $timesheet): array
    {
        $totalHours = 0;
        $clients = [];

        foreach ($timesheet->getTimeSheetEntries() as $entry) {
            $totalHours += $entry->getHours();
            $clients[$entry->getClient()->getId()] = $entry->getClient()->getName();
        }

        return [
            'id' => $timesheet->getId(),
            'user' => [
                'firstName' => $timesheet->getUser()->getFirstName(),
                'lastName' => $timesheet->getUser()->getLastName(),
            ],
            'startDate' => $timesheet->getStartDate()->format('Y-m-d'),
            'status' => $timesheet->getStatus()->value,
            'statusLabel' => $timesheet->getStatus()->label(),
            'totalHours' => (float) $totalHours,
            'clients' => array_values($clients),
            'submittedAt' => $timesheet->getSubmittedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Export Excel
     */
    #[Route('/export', name: 'api_timesheet_export', methods: ['GET'])]
    public function export(Request $request, TimeSheetEntryRepository $entryRepo): Response
    {
        try {
            $result = $entryRepo->findForExport($request);

            return $this->exportTimeSheetService->buildExcelExport($result['entries'], $result['fromDate'], $result['toDate']);

        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
