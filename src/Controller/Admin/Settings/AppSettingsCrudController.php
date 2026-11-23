<?php

namespace App\Controller\Admin\Settings;

use App\Entity\Settings\AppSettings;
use App\Repository\Settings\AppSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Image;

#[IsGranted('ROLE_ADMIN')]
class AppSettingsCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly AppSettingsRepository $settingsRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return AppSettings::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets->addAssetMapperEntry('app');
    }

    public function index(AdminContext $context): KeyValueStore|Response
    {
        return $this->redirect($this->editUrl());
    }

    /** Action « Rétablir les couleurs par défaut » */
    public function resetColors(AdminContext $context): Response
    {
        $settings = $this->settingsRepository->getSettings();
        $settings->resetColors();
        $this->em->flush();

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

        yield ColorField::new('tertiaryColor', 'Couleur d\'accent')
            ->setHelp('Dégradés, titres et mises en avant.');

        yield FormField::addFieldset('Logo')
            ->setHelp('Affiché dans le menu, sur les pages de connexion et dans les e-mails.');

        yield ImageField::new('logoFilename', 'Logo')
            ->setBasePath('upload/settings')
            ->setUploadDir('public/upload/settings')
            ->setUploadedFileNamePattern('logo-[timestamp].[extension]')
            ->setRequired(false)
            ->setFileConstraints(new Image(
                maxSize: '2M',
                mimeTypes: ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'],
                mimeTypesMessage: 'Formats acceptés : PNG, JPG, SVG ou WebP.',
            ))
            ->setHelp('PNG ou SVG avec fond transparent conseillé, 2 Mo maximum.');
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $unitOfWork = $entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSets();
        $changes = $unitOfWork->getEntityChangeSet($entityInstance);

        $colorsChanged = (bool) array_intersect(
            ['primaryColor', 'secondaryColor', 'tertiaryColor'],
            array_keys($changes)
        );
        $logoChanged = isset($changes['logoFilename']);
        $logoRemoved = $logoChanged && $entityInstance->getLogoFilename() === null;

        parent::updateEntity($entityManager, $entityInstance);

        $logoMessage = $logoRemoved ? 'Le logo a été supprimé' : 'Le logo a été modifié';

        match (true) {
            $colorsChanged && $logoChanged => $this->addFlash('success', 'Les couleurs ont été modifiées. ' . $logoMessage . '.'),
            $colorsChanged => $this->addFlash('success', 'Les couleurs ont été modifiées.'),
            $logoChanged => $this->addFlash('success', $logoMessage . '.'),
            default => $this->addFlash('info', 'Aucune modification.'),
        };
    }

    private function editUrl(): string
    {
        return $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::EDIT)
            ->setEntityId($this->settingsRepository->getSettings()->getId())
            ->generateUrl();
    }
}
