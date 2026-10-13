<?php

namespace App\Controller\User\ExpenseReport;

use App\Entity\ExpenseReport\ExpenseReport;
use App\Entity\User;
use App\Enum\ExpenseReport\Status;
use App\Enum\Notification\NotificationFlag;
use App\Repository\UserRepository;
use App\Service\Admin\Notification\NotificationService;
use App\Trait\ExpenseReportTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use Symfony\Bundle\SecurityBundle\Security;

class UserExpenseReportCrudController extends AbstractCrudController
{
    use ExpenseReportTrait;

    public static function getEntityFqcn(): string
    {
        return ExpenseReport::class;
    }

    public function __construct(
        private readonly Security $security,
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
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
                        'Remboursée' => Status::REFUNDED,
                        'Invalide' => Status::INVALID,
                    ])
                    ->canSelectMultiple()
                    ->renderExpanded()
            );
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Mes notes de frais')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer une note de frais')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Details de ma note de frais')
            ->showEntityActionsInlined()
            ->setDefaultSort([
                'createdAt' => 'DESC',
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $details = Action::new('details', '')
            ->setIcon('fa fa-eye')
            ->linkToCrudAction(Crud::PAGE_DETAIL);

        $actions->update(Crud::PAGE_DETAIL, Action::DELETE, function (Action $action) {
            return $action->displayIf(
                function (ExpenseReport $expenseReport) {
                    /** @var User $user */
                    $user = $this->getUser();

                    return $expenseReport->getStatus() === Status::PENDING && $expenseReport->getCreatedBy() === $user?->getFullName();
                }
            );
        });

        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Créer une note');
            })
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_INDEX, $details)
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
        $this->setRequestedBy($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);

        $admins = $this->userRepository->findByRole('ROLE_ADMIN');

        foreach ($admins as $admin) {
            $this->notificationService->sendNotification(
                $admin->getEmail(),
                NotificationFlag::ExpenseReportCreatedByUserToAdmin,
                ['employee' => $entityInstance->getRequestedBy()->getFullName()]
            );
        }

        $this->notificationService->sendNotification(
            $entityInstance->getRequestedBy()->getEmail(),
            NotificationFlag::ExpenseReportCreatedByUserToUser,
            [
                'employeeEmail' => $entityInstance->getRequestedBy()->getEmail(),
                'employeeName' => $entityInstance->getRequestedBy()->getFirstName(),
            ]
        );
    }

    private function setBlamable(ExpenseReport $entityInstance, bool $isNew): void
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        $fullName = $user->getFullName();

        if ($isNew) {
            $entityInstance->setCreatedBy($fullName);
        }
    }

    private function setRequestedBy(ExpenseReport $entityInstance): void
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        $entityInstance->setRequestedBy($user);
    }
}
