<?php

namespace App\Controller\Admin\ExpenseReport;

use App\Entity\ExpenseReport\ExpenseReport;
use App\Entity\User;
use App\Enum\ExpenseReport\Status;
use App\Enum\Notification\NotificationFlag;
use App\Repository\UserRepository;
use App\Service\Admin\Notification\NotificationService;
use App\Trait\ExpenseReportTrait;
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

class ExpenseReportCrudController extends AbstractCrudController
{
    use ExpenseReportTrait;

    public static function getEntityFqcn(): string
    {
        return ExpenseReport::class;
    }

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
        private readonly Security $security,
    ) {
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Notes de frais')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer une note de frais pour un employer')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Details de la note de frais')
            ->showEntityActionsInlined()
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
                        'Remboursée' => Status::REFUNDED,
                        'Invalide' => Status::INVALID,
                    ])
                    ->canSelectMultiple()
                    ->renderExpanded()
            )
            ->add(EntityFilter::new('requestedBy', 'Demandeur'));
    }

    public function configureActions(Actions $actions): Actions
    {
        $details = Action::new('details', '')
            ->setIcon('fa fa-eye')
            ->linkToCrudAction(Crud::PAGE_DETAIL);

        $actions->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) {
            /** @var User $user */
            $user = $this->getUser();

            return $action
                ->setCssClass('btn-danger action-delete')
                ->displayIf(
                    fn (ExpenseReport $expenseReport) => $expenseReport->getStatus() === Status::PENDING
                        && $expenseReport->getCreatedBy() === $user->getFullName()
                );
        });

        $refunded = Action::new('refunded', 'Rembourser')
                    ->setIcon('fa fa-thumbs-up')
                    ->setCssClass('btn-success')
                    ->displayIf(fn (ExpenseReport $expenseReport) => $expenseReport->getStatus() === Status::PENDING)
                    ->linkToRoute('refunded', fn (ExpenseReport $expenseReport) => [
                        'id' => $expenseReport->getId(),
                    ]);

        $invalid = Action::new('invalid', 'Invalider')
            ->setIcon('fa fa-exclamation-triangle')
            ->setCssClass('btn-warning')
            ->displayIf(fn (ExpenseReport $expenseReport) => $expenseReport->getStatus() === Status::PENDING)
            ->linkToRoute('expense_report_invalid', fn (ExpenseReport $expenseReport) => [
                'id' => $expenseReport->getId(),
            ]);

        $requestActions = ActionGroup::new('actions', '')
            ->setIcon('fa fa-ellipsis-h')
            ->setCssClass('btn-invisible')
            ->setLabel(false)
            ->addAction($refunded)
            ->addAction($invalid)
        ;

        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Créer une note');
            })
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_INDEX, $requestActions)
            ->add(Crud::PAGE_INDEX, $details)
            ->add(Crud::PAGE_DETAIL, $invalid)
            ->add(Crud::PAGE_DETAIL, $refunded)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DELETE);

    }

    #[\Override]
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof ExpenseReport) {
            parent::persistEntity($entityManager, $entityInstance);

            return;
        }

        $this->setBlamable($entityInstance, true);

        parent::persistEntity($entityManager, $entityInstance);

        $admins = $this->userRepository->findByRole('ROLE_ADMIN');

        foreach ($admins as $admin) {
            $this->notificationService->sendNotification(
                $admin->getEmail(),
                NotificationFlag::ExpenseReportCreatedByAdminToAdmin,
                ['employee' => $entityInstance->getRequestedBy()->getFullName()]
            );
        }

        $this->notificationService->sendNotification(
            $entityInstance->getRequestedBy()->getEmail(),
            NotificationFlag::ExpenseReportCreatedByAdminToUser,
            ['employeeEmail' => $entityInstance->getRequestedBy()->getEmail(),
                'employeeName' => $entityInstance->getRequestedBy()->getFirstName()]
        );
    }

    private function setBlamable(ExpenseReport $entityInstance, bool $isNew): void
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return;
        }

        $fullName = $user->getFullName();

        if ($isNew) {
            $entityInstance->setCreatedBy($fullName);
        }
    }
}
