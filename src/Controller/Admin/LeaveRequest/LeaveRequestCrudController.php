<?php

namespace App\Controller\Admin\LeaveRequest;

use App\Entity\LeaveRequest\LeaveRequest;
use App\Entity\User;
use App\Enum\LeaveRequest\Status;
use App\Enum\Notification\NotificationFlag;
use App\Repository\UserRepository;
use App\Service\Admin\LeaveRequest\BlamableService;
use App\Service\Admin\LeaveRequest\LeaveRequestBalanceCheckerService;
use App\Service\Admin\LeaveRequest\LeaveRequestBalanceService;
use App\Service\Admin\LeaveRequest\LeaveRequestTypeService;
use App\Service\Admin\Notification\NotificationService;
use App\Trait\LeaveRequestTrait;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Bundle\SecurityBundle\Security;

class LeaveRequestCrudController extends AbstractCrudController
{
    use LeaveRequestTrait;

    public static function getEntityFqcn(): string
    {
        return LeaveRequest::class;
    }

    public function __construct(
        private readonly Security $security,
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
        private readonly LeaveRequestBalanceCheckerService $leaveRequestBalanceCheckerService,
        private readonly LeaveRequestBalanceService $leaveRequestBalanceService,
        private readonly BlamableService $blamableService,
        private readonly LeaveRequestTypeService $leaveRequestTypeService,
    ) {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Demandes de congé')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer une demande pour un employé')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Details de la demande')
            ->showEntityActionsInlined()
            ->addFormTheme('form/admin/common/period.html.twig')
            ->setDefaultSort([
                'createdAt' => 'DESC',
            ]);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addAssetMapperEntry('app');
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(
                ChoiceFilter::new('status', 'Statut')
                    ->setChoices([
                        'En attente' => Status::PENDING,
                        'Annulation demandée' => Status::CANCELLATION_REQUESTED,
                        'Acceptée' => Status::CONFIRMED,
                        'Refusé' => Status::DENIED,
                        'Invalide' => Status::INVALID,
                        'Annulée' => Status::CANCELLED,
                    ])
                    ->canSelectMultiple()
                    ->renderExpanded()
            )
            ->add(EntityFilter::new('requestedBy', 'Demandeur'))
            ->add(EntityFilter::new('reason', 'Raison'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $details = Action::new('details', '')
            ->setIcon('fa fa-eye')
            ->linkToCrudAction(Crud::PAGE_DETAIL);

        $confirmed = Action::new('confirmed', 'Accepter')
            ->setIcon('fa fa-thumbs-up')
            ->setCssClass('btn-success')
            ->displayIf(fn (LeaveRequest $leaveRequest) => $leaveRequest->getStatus() === Status::PENDING)
            ->linkToRoute('confirmed', fn (LeaveRequest $leaveRequest) => [
                'id' => $leaveRequest->getId(),
            ]);

        $invalid = Action::new('invalid', 'Invalider')
            ->setIcon('fa fa-exclamation-triangle')
            ->setCssClass('btn-warning')
            ->displayIf(fn (LeaveRequest $leaveRequest) => $leaveRequest->getStatus() === Status::PENDING)
            ->linkToRoute('admin_leave_request_invalid', fn (LeaveRequest $leaveRequest) => [
                'id' => $leaveRequest->getId(),
            ]);

        $deny = Action::new('denied', 'Refuser')
            ->setIcon('fa fa-times')
            ->setCssClass('btn-danger')
            ->displayIf(fn (LeaveRequest $leaveRequest) => $leaveRequest->getStatus() === Status::PENDING)
            ->linkToRoute('admin_leave_request_deny', fn (LeaveRequest $leaveRequest) => [
                'id' => $leaveRequest->getId(),
            ]);

        $cancelled = Action::new('cancel', 'Annuler')
            ->setIcon('fa fa-undo')
            ->displayIf(fn (LeaveRequest $leaveRequest) => $leaveRequest->getStatus() === Status::CANCELLATION_REQUESTED)
            ->linkToRoute('admin_leave_request_cancel', fn (LeaveRequest $leaveRequest) => [
                'id' => $leaveRequest->getId(),
            ]);

        $actions->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) {
            return $action
                ->setCssClass('action-delete')
                ->displayIf(
                    function (LeaveRequest $leaveRequest) {
                        /** @var User $user */
                        $user = $this->getUser();

                        return $leaveRequest->getStatus() === Status::PENDING && $leaveRequest->getCreatedBy() === $user?->getFullName();
                    }
                );
        });


        $export = Action::new('calcul', 'Exporter')
            ->setIcon('fa fa-download')
            ->createAsGlobalAction()
            ->linkToUrl($this->generateUrl('calcul'));

        $requestActions = ActionGroup::new('actions', '')
            ->setIcon('fa fa-ellipsis-h')
            ->setCssClass('btn-invisible')
            ->setLabel(false)
            ->addAction($confirmed)
            ->addAction($invalid)
            ->addAction($deny)
            ->addAction($cancelled)
        ;

        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Créer une demande');
            })
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_INDEX, $export)
            ->add(Crud::PAGE_INDEX, $requestActions)
            ->add(Crud::PAGE_INDEX, $details)
            ->add(Crud::PAGE_DETAIL, $deny)
            ->add(Crud::PAGE_DETAIL, $invalid)
            ->add(Crud::PAGE_DETAIL, $cancelled)
            ->add(Crud::PAGE_DETAIL, $confirmed)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
        ;
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[\Override]
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof LeaveRequest) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        $this->leaveRequestTypeService->applyLeaveRequestType($entityInstance);

        $result = $this->leaveRequestBalanceCheckerService->check($entityInstance, true);

        if (!$result->valid) {
            $this->addFlash('danger', $result->message);

            return;
        }

        $this->blamableService->setBlamable($entityInstance, true);
        $this->leaveRequestBalanceService->deduct($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);

        $admins = $this->userRepository->findByRole('ROLE_ADMIN');

        foreach ($admins as $admin) {
            $this->notificationService->sendNotification(
                $admin->getEmail(),
                NotificationFlag::LeaveRequestCreatedByAdminToAdmin,
                ['employee' => $entityInstance->getRequestedBy()->getFullName()]
            );
        }

        $this->notificationService->sendNotification(
            $entityInstance->getRequestedBy()->getEmail(),
            NotificationFlag::LeaveRequestCreatedByAdminToUser,
            [
                'employeeEmail' => $entityInstance->getRequestedBy()->getEmail(),
                'employeeName' => $entityInstance->getRequestedBy()->getFirstName(),
            ]
        );
    }
}
