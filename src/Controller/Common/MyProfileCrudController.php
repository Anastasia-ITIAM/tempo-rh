<?php

namespace App\Controller\Common;

use App\Entity\User;
use App\Service\User\ProfileService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;

class MyProfileCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly Security $security,
        private readonly ProfileService $profileService,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        /** @var User $currentUser */
        $currentUser = $this->security->getUser();
        if ($currentUser instanceof User) {
            $qb->andWhere('entity.id = :id')
                ->setParameter('id', $currentUser->getId());
        }

        return $qb;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ->setEntityLabelInSingular('Mon profil')
            ->setPageTitle(Crud::PAGE_INDEX, 'Mon profil')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier mon profil');
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addAssetMapperEntry('app');
    }

    public function configureFields(string $pageName): iterable
    {

        yield TextField::new('lastName', 'Nom')
            ->setRequired(true);
        yield TextField::new('firstName', 'Prénom')
            ->setRequired(true);
        yield TextField::new('email', 'Email')
            ->setRequired(true);
        yield TextField::new('plainPassword', 'Mot de passe')
            ->onlyOnForms();
        yield ChoiceField::new('roles', 'Role')
            ->setChoices([
                'Admin' => 'ROLE_ADMIN',
                'User' => 'ROLE_USER',
            ])
            ->onlyOnIndex();

        //        $user = $this->security->getUser();
        //
        //        $remainingCP = 0;
        //        $remainingRTT = 0;
        //
        //        if ($user) {
        //            foreach ($user->getLeaveTypes() as $leaveType) {
        //
        //                if ($leaveType->isExpired()) {
        //                    continue;
        //                }
        //
        //                match ($leaveType->getCode()->value) {
        //                    'CP' => $remainingCP = $leaveType->getCurrentNumber(),
        //                    'RTT' => $remainingRTT = $leaveType->getCurrentNumber(),
        //                    default => null,
        //                };
        //            }
        //        }
        //        yield NumberField::new('cpRemaining', 'Solde CP')
        //            ->onlyOnIndex()
        //            ->formatValue(function () use ($remainingCP) {
        //                return $remainingCP;
        //            });
        //
        //        yield NumberField::new('rttRemaining', 'Solde RTT')
        //            ->onlyOnIndex()
        //            ->formatValue(function ($value, $entity) use ($remainingRTT) {
        //                return $remainingRTT;
        //            });
        yield DateTimeField::new('createdAt', 'Créé le')
            ->onlyOnIndex()
            ->setFormat('short', 'none')
            ->formatValue(fn ($value) => $value?->format('d/m/y'));
        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->onlyOnIndex()
            ->setFormat('short', 'none')
            ->formatValue(fn ($value) => $value?->format('d/m/y'));
        yield TextField::new('createdBy', 'Créé par')
            ->onlyOnIndex();
        yield TextField::new('updatedBy', 'Modifié par')
            ->onlyOnIndex();

    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action
                    ->setLabel(false);
            })
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE);
    }

    #[\Override]
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var User $entityInstance */
        $this->profileService->setPassword($entityInstance);
        $this->profileService->setBlamable($entityInstance, false);

        parent::updateEntity($entityManager, $entityInstance);
    }
}
