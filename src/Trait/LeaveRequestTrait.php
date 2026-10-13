<?php

namespace App\Trait;

use App\Controller\Admin\LeaveRequest\LeaveRequestCrudController;
use App\Controller\User\LeaveRequest\UserLeaveRequestCrudController;
use App\Entity\User;
use App\Enum\LeaveRequest\LeaveTypeCode;
use App\Form\LeaveRequest\PeriodType;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

trait LeaveRequestTrait
{
    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('statusLabel', 'Statut')
            ->onlyOnIndex()
            ->hideOnForm()
            ->formatValue(function ($value, $entity) {
                return sprintf(
                    '<span class="badge badge-%s">%s</span>',
                    $entity->getStatusType(),
                    $value
                );
            })
            ->renderAsHtml();

        yield TextField::new('statusLabel', 'Statut de la demande')
            ->onlyOnIndex()
            ->hideOnForm()
            ->formatValue(function ($value, $entity) {
                return sprintf(
                    '<span class="badge badge-%s">%s</span>',
                    $entity->getStatusType(),
                    $value
                );
            })
            ->renderAsHtml()
            ->onlyOnDetail();

        if ($this instanceof LeaveRequestCrudController) {
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

        yield FormField::addFieldset('Choisissez la raison')
            ->onlyWhenCreating();

        yield AssociationField::new('reason', 'Raison');

        yield AssociationField::new('reason', 'Raison')
            ->onlyWhenCreating()
            ->setLabel(false)
            ->setColumns(3)
            ->setRequired(true);

        if ($this instanceof LeaveRequestCrudController) {
            yield FormField::addFieldset('Solde du demandeur')
                ->setHelp('<span id="leave-balance">CP: -- | RTT: --</span>')
                ->onlyWhenCreating();
        }

        if ($this instanceof UserLeaveRequestCrudController) {

            /** @var User $user */
            $user = $this->security->getUser();

            $remainingCP = 0;
            $remainingRTT = 0;

            $format = function ($value) {
                $value = (float) $value;

                if (fmod($value, 1.0) === 0.0) {
                    return (string) (int) $value;
                }

                return number_format($value, 1, '.');
            };

            if ($user) {
                foreach ($user->getLeaveTypes() as $leaveType) {

                    if ($leaveType->isExpired()) {
                        continue;
                    }

                    match ($leaveType->getCode()) {
                        LeaveTypeCode::CP => $remainingCP = $leaveType->getCurrentNumber(),
                        LeaveTypeCode::RTT => $remainingRTT = $leaveType->getCurrentNumber(),
                        default => null,
                    };
                }
            }

            yield FormField::addFieldset(sprintf(
                'Mon solde : CP : %s | RTT : %s',
                $format($remainingCP),
                $format($remainingRTT)
            ))
                ->onlyWhenCreating();
        }

        yield FormField::addFieldset('Choisissez les dates')
            ->onlyWhenCreating();

        yield TextField::new('period', 'Période')
            ->setLabel(false)
            ->setRequired(true)
            ->setFormType(PeriodType::class)
            ->onlyWhenCreating();

        yield TextField::new('period', 'Période')
            ->formatValue(function ($value, $entity) {
                $period = $entity->getPeriod();

                if (!$period) {
                    return '';
                }

                $start = $period->getStartedAt()?->format('d/m/y');
                $end = $period->getEndedAt()?->format('d/m/y');

                return "$start → $end";
            })
            ->onlyOnIndex();

        yield TextField::new('period', 'Période')
            ->formatValue(function ($value, $entity) {
                $period = $entity->getPeriod();

                if (!$period) {
                    return '';
                }

                $start = $period->getStartedAt()?->format('d/m/y H:i');
                $end = $period->getEndedAt()?->format('d/m/y H:i');

                return "$start → $end";
            })
            ->onlyOnDetail();

        $format = function ($value) {
            $value = (float) $value;

            if (fmod($value, 1.0) === 0.0) {
                return (string) (int) $value;
            }

            return number_format($value, 1, ',');
        };

        yield NumberField::new('cpDays', 'CP')
            ->onlyOnDetail()
            ->formatValue(fn ($value) => $format($value));

        yield FormField::addFieldset('Nombre de jours RTT')
            ->onlyWhenCreating();

        if ($this instanceof UserLeaveRequestCrudController) {
            /** @var User $user */
            $user = $this->security->getUser();
            $remainingRTT = 0;


            if ($user instanceof User) {
                $remainingRTT = $user->getRttRemaining();
            }

            yield NumberField::new('rtt', 'RTT')
                ->hideOnIndex()
                ->setRequired(true)
                ->setColumns(2)
                ->formatValue(fn ($value) => $format($value))
                ->setFormTypeOption('data', 0)
                ->setFormTypeOption('html5', true)
                ->setFormTypeOption('attr', [
                    'min' => 0,
                    'max' => $remainingRTT,
                    'step' => 0.5,
                ]);
        }

        if ($this instanceof LeaveRequestCrudController) {
            yield NumberField::new('rtt', 'RTT')
                ->hideOnIndex()
                ->setRequired(true)
                ->setColumns(2)
                ->formatValue(fn ($value) => $format($value))
                ->setFormTypeOption('data', 0)
                ->setFormTypeOption('html5', true)
                ->setFormTypeOption('attr', [
                    'min' => 0,
                    'max' => 12,
                    'step' => 0.5,
                ]);
        }

        if ($this instanceof LeaveRequestCrudController) {
            yield FormField::addFieldset('Commentaire')
                ->onlyWhenCreating();
        }

        if ($this instanceof LeaveRequestCrudController) {
            yield TextareaField::new('commentAdmin', "")
                ->setLabel(false)
                ->setColumns(4)
                ->onlyWhenCreating()
                ->setRequired(true);
        }

        yield TextareaField::new('commentAdmin', "Commentaire d'administrateur")
            ->onlyOnDetail();

        yield TextareaField::new('commentUser', "Commentaire d'employé")
            ->onlyOnDetail();

        yield TextField::new('createdBy', 'Crée par')
            ->hideWhenCreating();

        yield TextField::new('updatedBy', 'Modifié par')
            ->hideWhenCreating();

        yield DateTimeField::new('createdAt', 'Créé le')
            ->onlyOnIndex()
            ->setFormat('short', 'none')
            ->formatValue(fn ($value) => $value?->format('d/m/y'));

        yield DateTimeField::new('createdAt', 'Créé le')
            ->onlyOnDetail()
            ->setFormat('medium', 'short');

        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->onlyOnIndex()
            ->setFormat('short', 'none')
            ->formatValue(fn ($value) => $value?->format('d/m/y'));

        yield DateTimeField::new('updatedAt', 'Modifié le')
            ->onlyOnDetail()
            ->setFormat('medium', 'short');
    }
}
