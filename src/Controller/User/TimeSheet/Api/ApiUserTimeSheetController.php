<?php

namespace App\Controller\User\TimeSheet\Api;

use App\Entity\TimeSheet\Client;
use App\Entity\TimeSheet\Project;
use App\Entity\TimeSheet\Task;
use App\Entity\TimeSheet\TimeSheetWeek;
use App\Entity\User;
use App\Enum\Notification\NotificationFlag;
use App\Enum\TimeSheet\Status;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Repository\TimeSheetEntryRepository;
use App\Repository\TimeSheetWeekRepository;
use App\Service\Admin\Notification\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/timesheet')]
class ApiUserTimeSheetController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TimeSheetWeekRepository $weekRepo,
        private readonly TimeSheetEntryRepository $entryRepo,
        private readonly ProjectRepository $projectRepo,
        private readonly TaskRepository $taskRepo,
        private readonly NotificationService $notificationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    // ===== CASCADE =====

    #[Route('/ajax/projects', name: 'ajax_projects')]
    public function projects(Request $request): JsonResponse
    {
        $clientId = (int) $request->query->get('client');
        if (!$clientId) {
            return $this->json([]);
        }

        $client = $this->em->find(Client::class, $clientId);
        if (!$client || !$client->isActive()) {
            return $this->json([]);
        }

        $projects = $this->projectRepo->findByClient($client);

        return $this->json(array_map(fn ($p) => [
            'id' => $p->getId(),
            'name' => $p->getName(),
        ], $projects));
    }

    #[Route('/ajax/tasks', name: 'ajax_tasks')]
    public function tasks(Request $request): JsonResponse
    {
        $projectId = (int) $request->query->get('project');
        if (!$projectId) {
            return $this->json([]);
        }

        $project = $this->em->find(Project::class, $projectId);
        if (!$project || !$project->isActive()) {
            return $this->json([]);
        }

        $tasks = $this->taskRepo->findByProject($project);

        return $this->json(array_map(fn ($t) => [
            'id' => $t->getId(),
            'name' => $t->getName(),
        ], $tasks));
    }

    // ===== ENTRIES =====
    #[Route('/entry/upsert', name: 'api_entry_upsert', methods: ['POST'])]
    public function upsertEntry(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $timesheetWeekId = (int) ($data['timesheetWeekId'] ?? 0);
        $clientId = (int) ($data['clientId'] ?? 0);
        $projectId = (int) ($data['projectId'] ?? 0);
        $taskId = (int) ($data['taskId'] ?? 0);
        $dateStr = $data['date'] ?? null;
        $hours = (float) ($data['hours'] ?? 0);
        $comment = $data['comment'] ?? null;
        $weekStart = $data['weekStart'] ?? date('Y-m-d');

        if (!$clientId || !$projectId || !$taskId || !$dateStr) {
            return $this->json(['error' => 'Données incomplètes'], 400);
        }

        try {
            // chercher la TimeSheetWeek
            $timesheet = $this->em->find(TimeSheetWeek::class, $timesheetWeekId);

            //  si elle n'existe pas → créer une nouvelle
            if (!$timesheet) {
                $timesheet = $this->weekRepo->findOrCreateWeekForUser($user, $weekStart);
            }

            if ($timesheet->getUser()?->getId() !== $user->getId()) {
                return $this->json(['error' => 'Accès refusé'], 403);
            }

            if (!$timesheet->isEditable()) {
                return $this->json(['error' => 'Cette feuille de temps ne peut plus être modifiée'], 403);
            }

            $client = $this->em->find(Client::class, $clientId);
            $project = $this->em->find(Project::class, $projectId);
            $task = $this->em->find(Task::class, $taskId);

            if (!$client || !$project || !$task) {
                return $this->json(['error' => 'Client, projet ou tâche introuvable'], 400);
            }

            $entry = $this->entryRepo->findOrCreateEntry($timesheet, new \DateTime($dateStr), $client, $project, $task);

            $entry->setHours((string) $hours);
            if ($comment !== null) {
                $entry->setComment($comment);
            }

            $this->em->persist($entry);
            $this->em->flush();

            return $this->json(['success' => true, 'id' => $entry->getId()]);
        } catch (\Exception $e) {
            return $this->serverError($e);
        }
    }

    #[Route('/entry/row', name: 'api_entry_row_delete', methods: ['DELETE'])]
    public function deleteRow(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);

        $timesheetWeekId = (int) ($data['timesheetWeekId'] ?? 0);
        $clientId = (int) ($data['clientId'] ?? 0);
        $projectId = (int) ($data['projectId'] ?? 0);
        $taskId = (int) ($data['taskId'] ?? 0);

        try {
            $timesheet = $this->em->find(TimeSheetWeek::class, $timesheetWeekId);
            $client = $this->em->find(Client::class, $clientId);
            $project = $this->em->find(Project::class, $projectId);
            $task = $this->em->find(Task::class, $taskId);

            if (!$timesheet || $timesheet->getUser()?->getId() !== $user->getId()) {
                return $this->json(['error' => 'Accès refusé'], 403);
            }

            if (!$timesheet->isEditable()) {
                return $this->json(['error' => 'Cette feuille de temps ne peut plus être modifiée'], 403);
            }

            if (!$client || !$project || !$task) {
                return $this->json(['error' => 'Client, projet ou tâche introuvable'], 400);
            }

            $this->entryRepo->deleteRow($timesheet, $client, $project, $task);
            $this->em->flush();

            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->serverError($e);
        }
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[Route('/timesheet/{id}/submit', name: 'api_timesheet_submit', methods: ['POST'])]
    public function submitTimesheet(TimeSheetWeek $timesheet): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($timesheet->getUser()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        if (!$timesheet->isEditable()) {
            return $this->json(['error' => 'Cette feuille de temps a déjà été soumise'], 400);
        }

        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));


        $timesheet->setStatus(Status::SUBMITTED);
        $timesheet->setSubmittedAt($now);
        $this->em->flush();

        $this->notificationService->notifyAdmins(
            NotificationFlag::TimeSheetWeekSubmittedByUserToAdmin,
            [
                'employee' => $timesheet->getUser()->getFullName(),
            ]
        );

        $this->notificationService->sendNotification(
            $timesheet->getUser()->getEmail(),
            NotificationFlag::TimeSheetWeekSubmittedByUserToUser,
            [
                'employeeName' => $timesheet->getUser()->getFirstName(),
            ]
        );

        $this->addFlash('warning', 'Feuille de temps soumise avec succès !');


        return $this->json(['success' => true, 'status' => $timesheet->getStatus()->value]);
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[Route('/timesheet/copy-previous-week', name: 'api_timesheet_copy_previous', methods: ['POST'])]
    public function copyPreviousWeek(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), true);
        $weekStart = $data['weekStart'] ?? null;

        if (!$weekStart) {
            return $this->json(['error' => 'Semaine manquante'], 400);
        }

        $this->em->getFilters()->disable('softdeleteable');

        $previousStart = new \DateTime($weekStart)->modify('-7 days')->format('Y-m-d');
        $previousWeek = $this->weekRepo->findCurrentWeekForUser($user, $previousStart);

        if (!$previousWeek || count($previousWeek->getGroupedRows() ?? []) === 0) {
            return $this->json(['error' => 'Aucun projet la semaine précédente'], 404);
        }

        $currentWeek = $this->weekRepo->findOrCreateWeekForUser($user, $weekStart);

        if ($currentWeek->getStatus() !== Status::DRAFT) {
            return $this->json(['error' => 'La copie n\'est possible que sur une feuille en brouillon'], 403);
        }

        $monday = new \DateTime($weekStart);

        foreach ($previousWeek->getGroupedRows() as $row) {
            // On ne recopie pas un client / projet / tâche supprimé ou désactivé
            foreach ([$row['client'], $row['project'], $row['task']] as $item) {
                if ($item->getDeletedAt() !== null || (method_exists($item, 'isActive') && !$item->isActive())) {
                    continue 2;
                }
            }

            // Une ligne = au moins une entrée : on crée une entrée vide (0 h) le lundi
            $entry = $this->entryRepo->findOrCreateEntry($currentWeek, clone $monday, $row['client'], $row['project'], $row['task']);
            if ($entry->getId() === null) {
                $entry->setHours('0');
                $this->em->persist($entry);
            }
        }

        $this->em->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Erreur inattendue : le détail va dans les logs (var/log), le navigateur reçoit un message générique.
     */
    private function serverError(\Throwable $e): JsonResponse
    {
        $this->logger->error('Timesheet API (employé) : ' . $e->getMessage(), ['exception' => $e]);

        return new JsonResponse(['error' => 'Une erreur est survenue, réessayez plus tard.'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
