<?php

namespace App\Controller\User\TimeSheet;

use App\Controller\DashboardController;
use App\Entity\TimeSheet\TimeSheetWeek;
use App\Entity\User;
use App\Enum\TimeSheet\Status;
use App\Repository\ClientRepository;
use App\Repository\TimeSheetWeekRepository;
use App\Service\EasyAdmin\EasyAdminContextService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/timesheet/user')]
class UserTimesheetController extends AbstractController
{
    public function __construct(
        private readonly DashboardController $dashboardController,
        private readonly EasyAdminContextService $adminContextService
    ) {
        $this->adminContextService->setEasyAdminContext($this->dashboardController);
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[Route('/timesheet/{weekStart}', name: 'user_timesheet', defaults: ['weekStart' => null])]
    public function index(
        ?string $weekStart,
        TimeSheetWeekRepository $repo,
        ClientRepository $clientRepo,
        EntityManagerInterface $entityManager
    ): Response {
        // Permet d'afficher les entrées historiques même si le client/projet/tâche
        // liées a depuis été soft-deleted (sinon EntityNotFoundException dans le template)
        $entityManager->getFilters()->disable('softdeleteable');

        if (!$weekStart) {
            $today = new \DateTime();
            $monday = TimeSheetWeek::getMondayOfWeek($today);
            $weekStart = $monday->format('Y-m-d');

            return $this->redirectToRoute('user_timesheet', ['weekStart' => $weekStart]);
        }

        /** @var User $user */
        $user = $this->getUser();

        $timesheetWeek = $repo->findCurrentWeekForUser($user, $weekStart);

        // Si elle n'existe pas, créer un objet temporaire sans flush pour pouvoir afficher le formulaire
        if (!$timesheetWeek) {
            $timesheetWeek = new TimeSheetWeek();
            $timesheetWeek->setUser($user);
            $timesheetWeek->setStartDate(new \DateTime($weekStart));
            $timesheetWeek->setStatus(Status::DRAFT);
        }

        $clients = $clientRepo->findBy(['isActive' => true], ['name' => 'ASC']);

        $startDate = new \DateTime($weekStart);
        $endDate = (clone $startDate)->modify('+6 days');

        $weekDays = [];
        for ($i = 0; $i < 7; ++$i) {
            $weekDays[] = (clone $startDate)->modify("+$i days");
        }

        return $this->render('timesheet/user/user_timesheet.html.twig', [
            'timesheetWeek' => $timesheetWeek,
            'weekStart' => $startDate,
            'weekEnd' => $endDate,
            'weekDays' => $weekDays,
            'groupedRows' => $timesheetWeek->getGroupedRows() ?? [],
            'dailyTotals' => $timesheetWeek->getDailyTotals() ?? [],
            'clients' => $clients,
            'rejectionReason' => $timesheetWeek->getComment(),
        ]);
    }
}