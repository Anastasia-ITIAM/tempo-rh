<?php

namespace App\Form\LeaveRequest;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommentDateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('comment', TextareaType::class, [
                'label' => 'Commentaire',
                'required' => $options['isRequired'],
                'help' => "Vous pouvez ajouter un commentaire, si besoin",
            ])
            ->add('refundedAt', DateType::class, [
                'label' => 'Date de remboursement',
                'data' => new \DateTimeImmutable(),
                'widget' => 'single_text',
                'html5' => true,
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([

            'isRequired' => false,
        ]);
    }
}
