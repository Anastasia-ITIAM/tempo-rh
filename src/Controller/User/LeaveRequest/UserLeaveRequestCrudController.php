<?php

namespace App\Controller\User\LeaveRequest;

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
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\ActionGroup;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Bundle\SecurityBundle\Security;

class UserLeaveRequestCrudController extends AbstractCrudController
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

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        /** @var User $user */
        $user = $this->security->getUser();

        return $qb
            ->andWhere('entity.requestedBy = :user')
            ->setParameter('user', $user);
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
                        'Acceptée' => Status::CONFIRMED,
                        'Refusé' => Status::DENIED,
                        'Invalide' => Status::INVALID,
                        'Annulation demandée' => Status::CANCELLATION_REQUESTED,
                        'Annulée' => Status::CANCELLED,
                    ])
                    ->canSelectMultiple()
            )
            ->add(EntityFilter::new('reason', 'Raison'));
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Mes demandes de congés')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer une demande')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Details de ma demande')
            ->showEntityActionsInlined()
            ->addFormTheme('form/admin/common/period.html.twig')
            ->setDefaultSort([
                'createdAt' => 'DESC',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $details = Action::new('details', '')
            ->displayIf(static function ($entity) {
                return null !== $entity && Status::CONFIRMED !== $entity->getStatus();
            })
            ->setLabel(false)
            ->setIcon('fa fa-eye')
            ->linkToCrudAction(Crud::PAGE_DETAIL);


        $detailsDrop = Action::new('details', 'Details')
            ->setIcon('fa fa-eye')
            ->linkToCrudAction(Crud::PAGE_DETAIL);


        $actions->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
            return $action
                ->setCssClass('action-delete')
                ->displayIf(
                    fn (LeaveRequest $leaveRequest) => $leaveRequest->getStatus() === Status::PENDING
                        && $leaveRequest->getCreatedBy() === $this->security->getUser()?->getFullName()
                );
        });

        $actions->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) {
            return $action
                ->setCssClass('action-delete')
                ->displayIf(
                    fn (LeaveRequest $leaveRequest) => $leaveRequest->getStatus() === Status::PENDING
                        && $leaveRequest->getCreatedBy() === $this->security->getUser()?->getFullName()
                );
        });
        $cancellation_requested = Action::new('cancellation_requested', "Demander l'annulation")
            ->setIcon('fa fa-ban')
            ->setCssClass('btn-danger')
            ->displayIf(fn (LeaveRequest $leaveRequest) => $leaveRequest->getStatus() === Status::CONFIRMED
                && $leaveRequest->getPeriod()
                && $leaveRequest->getPeriod()->getStartedAt() >= new \DateTimeImmutable())
            ->linkToRoute('cancellation_requested', fn (LeaveRequest $leaveRequest) => [
                'id' => $leaveRequest->getId(),
            ]);

        $requestActions = ActionGroup::new('actions', '')
            ->displayIf(static function ($entity) {
                return null !== $entity && Status::CONFIRMED === $entity->getStatus();
            })
            ->setIcon('fa fa-ellipsis-h')
            ->setCssClass('btn-invisible')
            ->setLabel(false)
            ->addAction($detailsDrop)
            ->addAction($cancellation_requested);


        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Créer une demande');
            })
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_INDEX, $requestActions)
            ->add(Crud::PAGE_INDEX, $details)
            ->add(Crud::PAGE_DETAIL, $cancellation_requested)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DELETE);
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

        /** @var User $user */
        $user = $this->security->getUser();
        $entityInstance->setRequestedBy($user);
        $this->leaveRequestTypeService->applyLeaveRequestType($entityInstance);

        $result = $this->leaveRequestBalanceCheckerService->check($entityInstance, false);

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
                NotificationFlag::LeaveRequestCreatedByUserToAdmin,
                ['employee' => $entityInstance->getRequestedBy()->getFullName()]
            );
        }

        $this->notificationService->sendNotification(
            $entityInstance->getRequestedBy()->getEmail(),
            NotificationFlag::LeaveRequestCreatedByUserToUser,
            [
                'employeeEmail' => $entityInstance->getRequestedBy()->getEmail(),
                'employeeName' => $entityInstance->getRequestedBy()->getFirstName(),
            ]
        );
    }

    #[\Override]
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var LeaveRequest $entityInstance */
        $this->leaveRequestBalanceService->restore($entityInstance);
        parent::deleteEntity($entityManager, $entityInstance);
    }
}
