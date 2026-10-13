<?php

namespace App\Controller\Admin\ExpenseReport;

use App\Entity\ExpenseReport\ExpenseReport;
use App\Entity\User;
use App\Enum\ExpenseReport\Status;
use App\Enum\Notification\NotificationFlag;
use App\Form\LeaveRequest\CommentDateType;
use App\Repository\UserRepository;
use App\Service\Admin\Notification\NotificationService;
use Doctrine\ORM\EntityManagerInterface as ORMEntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/')]
final class RefundedController extends AbstractController
{
    public function __construct(
        private readonly ORMEntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
        private readonly RouterInterface $router,
    ) {
    }

    #[Route('/refunded/{id}', name: 'refunded')]
    public function cancelled(ExpenseReport $expenseReport, Request $request): Response
    {
        $form = $this->createForm(CommentDateType::class);

        $backUrl = $request->headers->get('referer')
            ?? $this->router->generate('admin_expense_report_index');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->get('comment')->getData();
            $expenseReport->setComment($comment);
            $expenseReport->setStatus(Status::REFUNDED);

            /** @var User $user */
            $user = $this->getUser();
            if ($user) {
                $expenseReport->setUpdatedBy($user->getFullName());
            }

            if (!$expenseReport->getRefundedAt()) {
                $expenseReport->setRefundedAt(new \DateTimeImmutable('now'));
            }
            $this->em->flush();

            $admins = $this->userRepository->findByRole('ROLE_ADMIN');

            foreach ($admins as $admin) {
                $this->notificationService->sendNotification(
                    $admin->getEmail(),
                    NotificationFlag::ExpenseReportRefundedToAdmin,
                    ['employee' => $expenseReport->getRequestedBy()->getFullName()]
                );
            }

            $this->notificationService->sendNotification(
                $expenseReport->getRequestedBy()->getEmail(),
                NotificationFlag::ExpenseReportRefundedToUser,
                [
                    'employeeEmail' => $expenseReport->getRequestedBy()->getEmail(),
                    'employeeName' => $expenseReport->getRequestedBy()->getFirstName(),
                    'adminComment' => $expenseReport->getComment(),
                ]
            );
            $this->addFlash('success', 'La note de frais  a été remboursée.');

            return $this->redirectToRoute('admin_expense_report_index');
        }

        return $this->render('form/admin/common/comment_date.html.twig', [
            'form' => $form->createView(),
            'backUrl' => $backUrl,
        ]);
    }
}
