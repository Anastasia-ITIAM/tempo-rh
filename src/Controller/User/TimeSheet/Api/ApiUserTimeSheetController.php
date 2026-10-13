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
use App\Repository\UserRepository;
use App\Service\Admin\Notification\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
    ) {
    }

    // ===== CASCADE =====

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
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

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
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

            $client = $this->em->find(Client::class, $clientId);
            $project = $this->em->find(Project::class, $projectId);
            $task = $this->em->find(Task::class, $taskId);

            $entry = $this->entryRepo->findOrCreateEntry($timesheet, new \DateTime($dateStr), $client, $project, $task);

            $entry->setHours((string) $hours);
            if ($comment !== null) {
                $entry->setComment($comment);
            }

            $this->em->persist($entry);
            $this->em->flush();

            return $this->json(['success' => true, 'id' => $entry->getId()]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        } catch (ORMException $e) {
        }

        return $this->json(['error' => 'Erreur inconnue'], 500);
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

            if (!$timesheet || $timesheet->getUser()->getId() !== $user->getId()) {
                return $this->json(['error' => 'Accès refusé'], 403);
            }

            $this->entryRepo->deleteRow($timesheet, $client, $project, $task);
            $this->em->flush();

            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        } catch (ORMException $e) {
        }

        return $this->json(['error' => 'Erreur inconnue'], 500);
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[Route('/timesheet/{id}/submit', name: 'api_timesheet_submit', methods: ['POST'])]
    public function submitTimesheet(TimeSheetWeek $timesheet): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($timesheet->getUser() !== $user) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));


        $timesheet->setStatus(Status::SUBMITTED);
        $timesheet->setSubmittedAt($now);
        $this->em->flush();

        $admins = $this->userRepository->findByRole('ROLE_ADMIN');

        foreach ($admins as $admin) {
            $this->notificationService->sendNotification(
                $admin->getEmail(),
                NotificationFlag::TimeSheetWeekSubmittedByUserToAdmin,
                [
                    'employee' => $timesheet->getUser()->getFullName(),
                ]
            );
        }

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
}
