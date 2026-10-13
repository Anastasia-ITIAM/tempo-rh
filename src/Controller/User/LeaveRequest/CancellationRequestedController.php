<?php

namespace App\Controller\User\LeaveRequest;

use App\Entity\LeaveRequest\LeaveRequest;
use App\Entity\LeaveRequest\LeaveType;
use App\Entity\User;
use App\Enum\LeaveRequest\Status;
use App\Enum\Notification\NotificationFlag;
use App\Form\LeaveRequest\CommentType;
use App\Repository\UserRepository;
use App\Service\Admin\LeaveRequest\LeaveTypeRenewalService;
use App\Service\Admin\Notification\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

#[Route('/')]
final class CancellationRequestedController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
        private readonly RouterInterface $router,
    ) {
    }

    #[Route('/cancellation_requested/{id}', name: 'cancellation_requested')]
    public function cancellationRequested(LeaveRequest $leaveRequest, Request $request): Response
    {
        $form = $this->createForm(CommentType::class, null, [
            'isRequired' => true,
            'help' => 'Un motif est obligatoire',
            'type' => 'danger',
            'context' => 'cancellation_request',
        ]);

        $backUrl = $request->headers->get('referer')
            ?? $this->router->generate('admin_user_leave_request_index');
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->get('comment')->getData();
            $leaveRequest->setCommentUser($comment);
            $leaveRequest->setStatus(Status::CANCELLATION_REQUESTED);

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
                    NotificationFlag::LeaveRequestCancellationRequestedToAdmin,
                    [
                        'employee' => $leaveRequest->getRequestedBy()->getFullName(),
                        'employeeName' => $leaveRequest->getRequestedBy()->getFirstName(),
                        'userComment' => $leaveRequest->getCommentUser(),
                    ]
                );
            }

            $this->notificationService->sendNotification(
                $leaveRequest->getRequestedBy()->getEmail(),
                NotificationFlag::LeaveRequestCancellationRequestedToUser,
                [
                    'employeeEmail' => $leaveRequest->getRequestedBy()->getEmail(),
                    'employeeName' => $leaveRequest->getRequestedBy()->getFirstName(),
                    'adminComment' => $leaveRequest->getCommentAdmin(),
                ]
            );

            $this->addFlash('danger', 'La demande d’annulation a été envoyée !');

            return $this->redirectToRoute('admin_user_leave_request_index');
        }

        return $this->render('form/admin/common/comment.html.twig', [
            'form' => $form->createView(),
            'backUrl' => $backUrl,
        ]);
    }

    #[Route('/test/renew/{id}')]
    public function test(
        LeaveType $leaveType,
        LeaveTypeRenewalService $service,
        EntityManagerInterface $em
    ) {
        dump('BEFORE', [
            'end' => $leaveType->getEndDate(),
            'archived' => $leaveType->getArchivedAt(),
        ]);

        $new = $service->renewIfExpired($leaveType);

        if ($new !== $leaveType) {
            $em->persist($new);
        }

        $em->flush();

        dd('AFTER', [
            'end' => $leaveType->getEndDate(),
            'archived' => $leaveType->getArchivedAt(),
        ]);
    }
}
