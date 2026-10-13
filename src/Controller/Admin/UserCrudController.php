<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\Notification\NotificationFlag;
use App\Repository\UserRepository;
use App\Service\Admin\Notification\NotificationService;
use App\Service\User\ProfileService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly NotificationService $notificationService,
        private readonly ProfileService $profileService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Utilisateurs')
            ->setEntityLabelInSingular('Utilisateur')
            ->showEntityActionsInlined()
            ->setDefaultSort([
                'createdAt' => 'DESC',
            ])
            ->setSearchFields(['id',
                'email',
                'lastName',
                'firstName',
                'roles',
                'createdAt',
                'updatedAt',
                'createdBy',
                'updatedBy',
            ]);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addAssetMapperEntry('app');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),
            TextField::new('lastName', 'Nom')
                ->setRequired(true),
            TextField::new('firstName', 'Prénom')
                ->setRequired(true),
            TextField::new('email', 'Email')
                ->setRequired(true),
            TextField::new('plainPassword', 'Mot de passe')
                ->setRequired(true)
                ->onlywhenCreating(),
            TextField::new('plainPassword', 'Mot de passe')
                ->setRequired(false)
                ->hideOnIndex(),
            ChoiceField::new('roles', 'Role')
                ->setColumns(3)
                ->setChoices([
                    'Admin' => 'ROLE_ADMIN',
                    'User' => 'ROLE_USER',
                ])
                ->allowMultipleChoices(),
            NumberField::new('cpRemaining', 'Solde CP'),
            NumberField::new('rttRemaining', 'Solde RTT'),
            DateTimeField::new('createdAt', 'Créé le')
                ->onlyOnIndex()
                ->setFormat('short', 'none')
                ->formatValue(function ($value) {
                    return $value?->format('d/m/y');
                }),
            DateTimeField::new('updatedAt', 'Modifié le')
                ->onlyOnIndex()
                ->setFormat('short', 'none')
                ->formatValue(function ($value) {
                    return $value?->format('d/m/y');
                }),
            TextField::new('createdBy', 'Créé par')
                ->onlyOnIndex(),
            TextField::new('updatedBy', 'Modifié par')
                ->onlyOnIndex(),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action
                    ->setLabel(false);
            })
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_EDIT, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE);


    }

    #[\Override]
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $entityInstance */
        $plainPassword= $entityInstance->getPlainPassword();

        $this->profileService->setPassword($entityInstance);
        $this->profileService->setBlamable($entityInstance, true);

        parent::persistEntity($entityManager, $entityInstance);

        $admins = $this->userRepository->findByRole('ROLE_ADMIN');

        foreach ($admins as $admin) {
            $this->notificationService->sendNotification(
                $admin->getEmail(),
                NotificationFlag::AccountCreationToAdmin,
                ['employee' => $entityInstance->getFullName()]
            );
        }

        $this->notificationService->sendNotification(
            $entityInstance->getEmail(),
            NotificationFlag::AccountCreationToUser,
            [
                'employeeName' => $entityInstance->getFirstName(),
                'userEmail' => $entityInstance->getEmail(),
            ]
        );
    }

    #[\Override]
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $entityInstance */
        $this->profileService->setPassword($entityInstance);
        $this->profileService->setBlamable($entityInstance, true);

        parent::updateEntity($entityManager, $entityInstance);
    }
}
