<?php

namespace App\Controller\Admin\ExpenseReport;

use App\Entity\ExpenseReport\ExpenseReport;
use App\Entity\User;
use App\Enum\ExpenseReport\Status;
use App\Enum\Notification\NotificationFlag;
use App\Form\LeaveRequest\CommentType;
use App\Repository\UserRepository;
use App\Service\Admin\Notification\NotificationService;
use Doctrine\ORM\EntityManagerInterface as ORMEntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/')]
final class InvalidController extends AbstractController
{
    public function __construct(
        private readonly ORMEntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
        private readonly RouterInterface $router,
    ) {
    }

    #[Route('/expense-report/invalid/{id}', name: 'expense_report_invalid')]
    public function invalid(ExpenseReport $expenseReport, Request $request): Response
    {

        $form = $this->createForm(CommentType::class, null, [
            'isRequired' => true,
            'type' => 'warning',
        ]);

        $backUrl = $request->headers->get('referer')
            ?? $this->router->generate('admin_expense_report_index');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->get('comment')->getData();
            $expenseReport->setComment($comment);
            $expenseReport->setStatus(Status::INVALID);

            $user = $this->getUser();
            /** @var User $user */
            if ($user) {
                $expenseReport->setUpdatedBy($user->getFullName());
            }

            $this->em->flush();

            $admins = $this->userRepository->findByRole('ROLE_ADMIN');

            foreach ($admins as $admin) {
                $this->notificationService->sendNotification(
                    $admin->getEmail(),
                    NotificationFlag::ExpenseReportInvalidToAdmin,
                    ['employee' => $expenseReport->getRequestedBy()->getFullName()]
                );
            }

            $this->notificationService->sendNotification(
                $expenseReport->getRequestedBy()->getEmail(),
                NotificationFlag::ExpenseReportInvalidToUser,
                [
                    'employeeEmail' => $expenseReport->getRequestedBy()->getEmail(),
                    'employeeName' => $expenseReport->getRequestedBy()->getFirstName(),
                    'adminComment' => $expenseReport->getComment(),
                ]
            );

            $this->addFlash('danger', 'La note a été marquée comme invalide !');

            return $this->redirectToRoute('admin_expense_report_index');
        }

        return $this->render('form/admin/common/comment.html.twig', [
            'form' => $form->createView(),
            'backUrl' => $backUrl,
        ]);
    }
}
