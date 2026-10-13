<?php

namespace App\Controller\Admin\TimeSheet;

use App\Controller\DashboardController;
use App\Entity\TimeSheet\TimeSheetWeek;
use App\Enum\TimeSheet\Status;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use App\Service\EasyAdmin\EasyAdminContextService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/timesheet')]
class AdminTimeSheetController extends AbstractController
{
    public function __construct(
        private readonly DashboardController $dashboardController,
        private readonly EasyAdminContextService $adminContextService,
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->adminContextService->setEasyAdminContext($this->dashboardController);
        $this->entityManager->getFilters()->disable('softdeleteable');
    }

    #[Route('', name: 'admin_timesheet_dashboard')]
    public function dashboard(
        UserRepository $userRepo,
        ClientRepository $clientRepo
    ): Response {
        $users = $userRepo->findAll();

        $statuses = [
            Status::SUBMITTED,
            Status::APPROVED,
            Status::REJECTED,
        ];

        return $this->render('timesheet/admin/dashboard_timesheet.html.twig', [
            'dateFrom' => new \DateTime('first day of this month')->format('Y-m-d'),
            'dateTo' => new \DateTime('last day of this month')->format('Y-m-d'),
            'users' => $users,
            'statuses' => $statuses,
            'clients' => $clientRepo->findAll(),
        ]);
    }

    #[Route('/week/{id}', name: 'admin_timesheet_week', requirements: ['id' => '\d+'])]
    public function weekDetail(
        TimeSheetWeek $timesheetWeek,
        ClientRepository $clientRepo
    ): Response {
        $startDate = $timesheetWeek->getStartDate();
        $endDate = (clone $startDate)->modify('+6 days');

        $weekDays = [];
        for ($i = 0; $i < 7; ++$i) {
            $weekDays[] = (clone $startDate)->modify("+$i days");
        }

        return $this->render('timesheet/admin/week_detail.html.twig', [
            'timesheetWeek' => $timesheetWeek,
            'weekStart' => $startDate,
            'weekEnd' => $endDate,
            'weekDays' => $weekDays,
            'groupedRows' => $timesheetWeek->getGroupedRows() ?? [],
            'dailyTotals' => $timesheetWeek->getDailyTotals() ?? [],
            'clients' => $clientRepo->findAll(),
        ]);
    }
}