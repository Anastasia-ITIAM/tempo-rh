<?php

namespace App\Controller\Admin\LeaveRequest;

use App\Entity\LeaveRequest\LeaveRequest;
use App\Entity\User;
use App\Enum\LeaveRequest\Status;
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
final class ConfirmedController extends AbstractController
{
    public function __construct(
        private readonly ORMEntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
        private readonly RouterInterface $router,
    ) {
    }

    #[Route('/confirmed/{id}', name: 'confirmed')]
    public function confirmed(LeaveRequest $leaveRequest, Request $request): Response
    {

        $form = $this->createForm(CommentType::class);

        $backUrl = $request->headers->get('referer')
            ?? $this->router->generate('admin_leave_request_index');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->get('comment')->getData();
            $leaveRequest->setCommentAdmin($comment);
            $leaveRequest->setStatus(Status::CONFIRMED);

            /** @var User $user */
            $user = $this->getUser();
            if ($user) {
                $leaveRequest->setUpdatedBy($user->getFullName());
            }

            $this->em->flush();

            $admins = $this->userRepository->findByRole('ROLE_ADMIN');

            foreach ($admins as $admin) {
                $this->notificationService->sendNotification(
                    $admin->getEmail(),
                    NotificationFlag::LeaveRequestConfirmedToAdmin,
                    ['employee' => $leaveRequest->getRequestedBy()->getFullName()]
                );
            }

            $this->notificationService->sendNotification(
                $leaveRequest->getRequestedBy()->getEmail(),
                NotificationFlag::LeaveRequestConfirmedToUser,
                [
                    'employeeEmail' => $leaveRequest->getRequestedBy()->getEmail(),
                    'employeeName' => $leaveRequest->getRequestedBy()->getFirstName(),
                    'adminComment' => $leaveRequest->getCommentAdmin(),
                ]
            );

            $this->addFlash('success', 'La demande a été acceptée.');

            return $this->redirectToRoute('admin_leave_request_index');
        }

        return $this->render('form/admin/common/comment.html.twig', [
            'form' => $form->createView(),
            'backUrl' => $backUrl,
        ]);
    }
}
