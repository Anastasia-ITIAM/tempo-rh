<?php

namespace App\Controller\User\LeaveRequest;

use App\Entity\LeaveRequest\LeaveRequest;
use App\Entity\User;
use App\Enum\LeaveRequest\Status;
use App\Enum\Notification\NotificationFlag;
use App\Form\LeaveRequest\CommentType;
use App\Security\Voter\OwnerVoter;
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
        private readonly NotificationService $notificationService,
        private readonly RouterInterface $router,
    ) {
    }

    #[Route('/cancellation_requested/{id}', name: 'cancellation_requested')]
    public function cancellationRequested(LeaveRequest $leaveRequest, Request $request): Response
    {
        $this->denyAccessUnlessGranted(OwnerVoter::OWNER, $leaveRequest);

        $startedAt = $leaveRequest->getPeriod()?->getStartedAt();
        if ($leaveRequest->getStatus() !== Status::CONFIRMED || !$startedAt || $startedAt < new \DateTimeImmutable()) {
            $this->addFlash('warning', 'Ce congé ne peut pas être annulé.');

            return $this->redirectToRoute('admin_user_leave_request_index');
        }

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

            $this->notificationService->notifyAdmins(
                NotificationFlag::LeaveRequestCancellationRequestedToAdmin,
                [
                    'employee' => $leaveRequest->getRequestedBy()->getFullName(),
                    'employeeName' => $leaveRequest->getRequestedBy()->getFirstName(),
                    'userComment' => $leaveRequest->getCommentUser(),
                ]
            );

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
}
