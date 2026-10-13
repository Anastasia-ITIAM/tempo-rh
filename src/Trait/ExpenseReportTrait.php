<?php

namespace App\Trait;

use App\Controller\Admin\ExpenseReport\ExpenseReportCrudController;
use App\Entity\User;
use App\Enum\ExpenseReport\Status;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Vich\UploaderBundle\Form\Type\VichFileType;

trait ExpenseReportTrait
{
    public function configureFields(string $pageName): iterable
    {

        if ($this instanceof ExpenseReportCrudController) {
            yield FormField::addFieldset('Status')
                ->hideOnDetail();

            yield ChoiceField::new('status', 'Status')
                ->setLabel(false)
                ->setColumns(2)
                ->onlyWhenCreating()
                ->setFormTypeOption('data', Status::REFUNDED)
                ->setChoices([
                    'En attente' => Status::PENDING,
                    'Remboursée' => Status::REFUNDED,
                    'Invalide' => Status::INVALID,
                ]);
        }

        yield TextField::new('statusLabel', 'Statut')
            ->setColumns(2)
            ->hideOnForm()
            ->formatValue(function ($value, $entity) {
                return sprintf(
                    '<span class="badge badge-%s">%s</span>',
                    $entity->getStatusType(),
                    $value
                );
            })
            ->renderAsHtml();

        if ($this instanceof ExpenseReportCrudController) {
            yield FormField::addFieldset('Demandeur')
                ->onlyWhenCreating();

            yield AssociationField::new('requestedBy', 'Demandeur')
                ->hideWhenCreating();

            /** @var User $user */
            $user = $this->security->getUser();

            yield AssociationField::new('requestedBy', 'Demandeur')
                ->onlyWhenCreating()
                ->setLabel(false)
                ->setColumns(3)
                ->setPermission('ROLE_ADMIN')
                ->setFormTypeOption('data', $user);
        }

        yield FormField::addFieldset('Titre')
            ->hideOnDetail();

        yield TextField::new('title', 'Titre')
            ->hideWhenCreating();

        yield TextField::new('title', 'Titre')
            ->onlyWhenCreating()
            ->setLabel(false)
            ->setColumns(3)
            ->setRequired(true);

        yield FormField::addFieldset('Date de facture')
            ->hideOnDetail();

        yield DateField::new('billedAt', 'Date de facture')
            ->hideWhenCreating();

        yield DateField::new('billedAt', 'Date de facture')
            ->onlyWhenCreating()
            ->setLabel(false)
            ->setRequired(true)
            ->setFormat('short', 'none')
            ->formatValue(function ($value) {
                return $value?->format('d/m/y');
            });


        yield FormField::addFieldset('Montant')
            ->hideOnDetail();

        yield MoneyField::new('amount', 'Montant')
            ->hideWhenCreating()
            ->setCurrency('EUR');

        yield MoneyField::new('amount', 'Montant')
            ->onlyWhenCreating()
            ->setLabel(false)
            ->setColumns(2)
            ->setCurrency('EUR')
            ->setRequired(true);


        yield DateField::new('refundedAt', 'Date de remboursement')
            ->onlyOnIndex()
            ->setFormat('short', 'none')
            ->setFormTypeOption('data', new \DateTimeImmutable())
            ->formatValue(function ($value) {
                return $value?->format('d/m/y');
            });

        yield DateTimeField::new('refundedAt', 'Date de remboursement')
            ->onlyOnDetail()
            ->setFormat('d MMMM yyyy');

        yield FormField::addFieldset('Facture à télécharger')
            ->hideOnDetail();

        yield Field::new('reportFile', 'Facture à télécharger')
            ->onlyWhenCreating()
            ->setLabel(false)
            ->setFormType(VichFileType::class)
            ->onlyOnForms()
            ->setRequired(true);

        yield TextField::new('path', 'Facture')
            ->onlyOnDetail()
            ->formatValue(function ($value, $entity) {
                $filename = $entity->getPath();

                if (!$filename) {
                    return '';
                }

                return sprintf(
                    '<a href="/upload/reports/%s" target="_blank">Télécharger</a>',
                    $filename
                );
            })
            ->renderAsHtml();

        if ($this instanceof ExpenseReportCrudController) {
            yield FormField::addFieldset('Commentaire')
                ->hideOnDetail();

            yield TextareaField::new('comment', 'Commentaire')
                ->onlyOnDetail();

            yield TextareaField::new('comment', 'Commentaire')
                ->onlyWhenCreating()
                ->setLabel(false)
                ->setColumns(4)
                ->hideOnIndex()
                ->setRequired(true);
        }

        yield TextField::new('createdBy', 'Crée par')
            ->hideWhenCreating();

        yield DateTimeField::new('createdAt', 'Créé le')
            ->onlyOnIndex()
            ->setFormat('short', 'none')
            ->formatValue(function ($value) {
                return $value?->format('d/m/y');
            });

        yield DateTimeField::new('createdAt', 'Créé le')
            ->onlyOnDetail()
            ->setFormat('medium', 'short');

        yield TextField::new('updatedBy', 'Modifié par')
            ->hideWhenCreating();

        yield DateTimeField::new('updatedAt', 'Modifié le')
//            ->hideOnIndex()
            ->onlyOnDetail()
            ->setFormat('medium', 'short');
    }
}
