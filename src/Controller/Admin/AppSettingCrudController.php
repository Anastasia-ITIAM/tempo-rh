<?php

namespace App\Controller\Admin;

use App\Entity\AppSetting;
use App\Repository\AppSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Paramètres → Personnalisation : une seule ligne de réglages, éditée dans EasyAdmin.
 */
#[IsGranted('ROLE_ADMIN')]
class AppSettingCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AppSettingRepository $repository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return AppSetting::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets->addAssetMapperEntry('app');
    }

    public function index(AdminContext $context): Response
    {
        return $this->redirect($this->editUrl());
    }

    /** Action « Rétablir les couleurs par défaut » */
    public function resetColors(AdminContext $context): Response
    {
        $settings = $this->currentSettings();
        $settings->resetColors();
        $this->entityManager->flush();

        $this->addFlash('success', 'Les couleurs par défaut ont été rétablies.');

        return $this->redirect($this->editUrl());
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Personnalisation')
            ->setPageTitle(Crud::PAGE_EDIT, 'Personnalisation');
    }

    public function configureActions(Actions $actions): Actions
    {
        $reset = Action::new('resetColors', 'Rétablir les couleurs par défaut', 'fa fa-rotate-left')
            ->linkToCrudAction('resetColors')
            ->setCssClass('btn btn-secondary');

        return $actions
            ->disable(Action::NEW, Action::DELETE, Action::DETAIL)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_EDIT, $reset);
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addFieldset('Couleurs de l\'interface')
            ->setHelp('Ces couleurs s\'appliquent aux boutons, liens, menus et pages de connexion.');

        yield ColorField::new('primaryColor', 'Couleur principale')
            ->setHelp('Boutons principaux, liens, éléments actifs.');

        yield ColorField::new('secondaryColor', 'Couleur secondaire')
            ->setHelp('Fond des pages de connexion et zones sombres.');

        yield ColorField::new('accentColor', 'Couleur d\'accent')
            ->setHelp('Dégradés, titres et mises en avant.');

        yield FormField::addFieldset('Logo')
            ->setHelp('PNG, JPG ou SVG, 1 Mo maximum.');

        yield ImageField::new('logoFilename', 'Logo')
            ->setBasePath('/upload/branding')
            ->setUploadDir('public/upload/branding')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->setRequired(false);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        // On compare avant l'enregistrement pour dire exactement ce qui a changé.
        $unitOfWork = $entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSets();
        $changes = $unitOfWork->getEntityChangeSet($entityInstance);

        $colorsChanged = (bool) array_intersect_key($changes, array_flip(['primaryColor', 'secondaryColor', 'accentColor']));
        $logoChanged = array_key_exists('logoFilename', $changes);

        parent::updateEntity($entityManager, $entityInstance);

        if ($colorsChanged && $logoChanged) {
            $this->addFlash('success', 'Les couleurs et le logo ont été modifiés.');
        } elseif ($colorsChanged) {
            $this->addFlash('success', 'Les couleurs ont été modifiées.');
        } elseif ($logoChanged) {
            $this->addFlash('success', 'Le logo a été modifié.');
        } else {
            $this->addFlash('info', 'Aucune modification à enregistrer.');
        }
    }

    /** Crée la ligne de réglages si elle n'existe pas encore. */
    private function currentSettings(): AppSetting
    {
        $settings = $this->repository->getCurrent();

        if (null === $settings->getId()) {
            $this->entityManager->persist($settings);
            $this->entityManager->flush();
        }

        return $settings;
    }

    private function editUrl(): string
    {
        return $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::EDIT)
            ->setEntityId($this->currentSettings()->getId())
            ->generateUrl();
    }
}
