<?php

namespace App\Controller\Admin\LeaveRequest;

use App\Entity\LeaveRequest\LeaveRequest;
use App\Entity\User;
use App\Enum\LeaveRequest\Status;
use App\Enum\Notification\NotificationFlag;
use App\Form\LeaveRequest\CommentType;
use App\Repository\UserRepository;
use App\Service\Admin\LeaveRequest\LeaveRequestBalanceService;
use App\Service\Admin\Notification\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/admin/leave-request')]
final class CancelledController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
        private readonly RouterInterface $router,
        private readonly LeaveRequestBalanceService $leaveRequestBalanceService,
    ) {
    }

    #[Route('/cancel/{id}', name: 'admin_leave_request_cancel')]
    public function cancel(LeaveRequest $leaveRequest, Request $request): Response
    {
        $form = $this->createForm(CommentType::class, null, [
            'type' => 'danger',
            'context' => 'canceled',
        ]);

        $backUrl = $request->headers->get('referer')
            ?? $this->router->generate('admin_leave_request_index');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->get('comment')->getData();
            $leaveRequest->setCommentAdmin($comment);
            $leaveRequest->setStatus(Status::CANCELLED);

            /** @var User $user */
            $user = $this->getUser();
            if ($user) {
                $leaveRequest->setUpdatedBy($user->getFullName());
            }

            $this->leaveRequestBalanceService->restore($leaveRequest);
            $this->em->flush();

            $admins = $this->userRepository->findByRole('ROLE_ADMIN');

            foreach ($admins as $admin) {
                $this->notificationService->sendNotification(
                    $admin->getEmail(),
                    NotificationFlag::LeaveRequestCancelledToAdmin,
                    ['employee' => $leaveRequest->getRequestedBy()->getFullName()]
                );
            }

            $this->notificationService->sendNotification(
                $leaveRequest->getRequestedBy()->getEmail(),
                NotificationFlag::LeaveRequestCancelledToUser,
                [
                    'employeeEmail' => $leaveRequest->getRequestedBy()->getEmail(),
                    'employeeName' => $leaveRequest->getRequestedBy()->getFirstName(),
                    'adminComment' => $leaveRequest->getCommentAdmin(),
                ]
            );

            $this->addFlash('danger', 'La demande a été annulée.');

            return $this->redirectToRoute('admin_leave_request_index');
        }

        return $this->render('form/admin/common/comment.html.twig', [
            'form' => $form->createView(),
            'backUrl' => $backUrl,
        ]);
    }
}
