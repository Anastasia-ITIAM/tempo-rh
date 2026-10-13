<?php

namespace App\Form\LeaveRequest;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentType extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['type'] = $options['type'];
        if ($options['type'] === 'danger') {
            $view->vars['icon_type'] = 'times';
        } elseif ($options['type'] === 'warning') {
            $view->vars['icon_type'] = 'exclamation-triangle';
        } else {
            $view->vars['icon_type'] = 'thumbs-up';
        }

        $view->vars['title'] = match ($options['context']) {
            'cancellation_request' => "Ajouter un motif",
            default => "Ajouter un commentaire",
        };

        $view->vars['submit_label'] ??= null;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['help']) {
            $help = $options['help'];
        } else {
            $help = $options['isRequired'] ? "Un commentaire est obligatoire" : "Vous pouvez ajouter un commentaire si besoin";
        }

        $submitLabel = match ($options['context']) {
            'cancellation_request' => "Demander d'annulation",
            'canceled' => 'Annuler',
            'rejected' => 'Rejeter',
            default => match ($options['type']) {
                'danger' => 'Refuser',
                'warning' => 'Invalider',
                default => 'Accepter',
            },
        };

        $builder
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => $options['isRequired'],
                'help' => $help,
            ]);

        $builder->setAttribute('submit_label', $submitLabel);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['submit_label'] = $form->getConfig()->getAttribute('submit_label');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'isRequired' => false,
            'help' => null,
            'type' => 'success',
            'context' => null,
        ]);
    }
}
