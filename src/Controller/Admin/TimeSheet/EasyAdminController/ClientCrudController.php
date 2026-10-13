<?php

namespace App\Controller\Admin\TimeSheet\EasyAdminController;

use App\Entity\TimeSheet\Client;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class ClientCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Client::class;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addAssetMapperEntry('app');
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_INDEX, 'Clients')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un client')
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Client')->setColumns(3);
        yield BooleanField::new('isActive', 'Actif');
    }

    public function configureActions(Actions $actions): Actions
    {
        $customDelete = Action::new('customDelete', 'Supprimer', 'fa fa-trash')
            ->linkToCrudAction('doDelete')
            ->setCssClass('btn btn-danger')
            ->setHtmlAttributes([
                'onclick' => "return confirm('Supprimer ce client va aussi supprimer tous ses projets et tâches associés. Continuer ?');",
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

        /** @var Client|null $client */
        $client = $this->entityManager->getRepository(Client::class)->find($entityId);

        if (!$client) {
            throw $this->createNotFoundException('Client introuvable.');
        }

        // Désactive le client et cascade sur ses projets/tâches
        $client->setIsActive(false);
        foreach ($client->getProjects() as $project) {
            $project->setIsActive(false);
            foreach ($project->getTasks() as $task) {
                $task->setIsActive(false);
            }
        }

        $this->entityManager->flush();


        $this->entityManager->remove($client);
        $this->entityManager->flush();

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }
}
