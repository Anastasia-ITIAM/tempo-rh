<?php

namespace App\Controller\Admin\TimeSheet\EasyAdminController;

use App\Entity\TimeSheet\Client;
use App\Entity\TimeSheet\Project;
use App\Entity\TimeSheet\Task;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TaskCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Task::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addAssetMapperEntry('app')
            ->addAssetMapperEntry('admin_task_client_filter');

    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInPlural('Taches')
            ->setEntityLabelInSingular('Tache')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer une tache')
            ->showEntityActionsInlined()
            ->setDefaultSort(['project.name' => 'ASC', 'name' => 'ASC']);

    }

    public function configureFields(string $pageName): iterable
    {
        yield Field::new('client', 'Client')
            ->setFormType(EntityType::class)
            ->setFormTypeOptions([
                'class' => Client::class,
                'choice_label' => 'name',
                'mapped' => false,
                'required' => true,
                'placeholder' => 'Choisir un client',
                'attr' => ['class' => 'js-client-filter'],
            ])
            ->setColumns(2)
            ->onlyWhenCreating();
        yield TextField::new('client', 'Client')
            ->setColumns(2)
            ->formatValue(fn ($value, $entity) => $entity->getClient()?->getName())
            ->hideWhenCreating()
            ->setFormTypeOption('disabled', true);
        yield AssociationField::new('project', 'Projet')
            ->onlyWhenCreating()
            ->setColumns(2)
            ->setFormTypeOptions([
                'required' => true,
                'attr' => ['class' => 'js-project-select'],
                'choice_attr' => fn (Project $project) => [
                    'data-client-id' => $project->getClient()?->getId(),
                ],
            ]);
        yield AssociationField::new('project', 'Projet')
            ->onlyOnIndex()
            ->setColumns(2);
        yield TextField::new('project', 'Projet')
            ->onlyWhenUpdating()
            ->setFormTypeOption('disabled', true)
             ->setColumns(2);
        yield ChoiceField::new('name', 'Tache')
            ->setChoices([
                'Développement' => 'Développement',
                'Réunion' => 'Réunion',
                'Analyse' => 'Analyse',
                'Gestion de projet' => 'Gestion de projet',
            ])
            ->setColumns(3);
        yield BooleanField::new('isActive', 'Active');
    }

    public function configureActions(Actions $actions): Actions
    {
        $customDelete = Action::new('customDelete', 'Supprimer', 'fa fa-trash')
            ->linkToCrudAction('doDelete')
            ->setCssClass('btn btn-danger')
            ->setHtmlAttributes([
                'onclick' => "return confirm('Supprimer cette tâche ?');",
            ]);

        return $actions
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_EDIT, $customDelete)
            ->remove(Crud::PAGE_INDEX, Action::DELETE);
    }

    public function doDelete(AdminContext $context): RedirectResponse
    {
        $entityId = $context->getRequest()->query->get('entityId');

        /** @var Task|null $task */
        $task = $this->entityManager->getRepository(Task::class)->find($entityId);

        if (!$task) {
            throw $this->createNotFoundException('Tâche introuvable.');
        }

        $task->setIsActive(false);
        $this->entityManager->flush();

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}
