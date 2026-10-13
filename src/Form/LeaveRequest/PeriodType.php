<?php

namespace App\Form\LeaveRequest;

use App\Entity\LeaveRequest\Period;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PeriodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startedAt', DateType::class, [
                'label' => 'Premier jour de congé',
                'widget' => 'single_text',
                'data' => new \DateTime(),
            ])
            ->add('startedAtTime', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Heure de début',
                'choices' => [
                    '08:00' => '08:00',
                    '12:00' => '12:00',
                ],
            ])
            ->add('endedAt', DateType::class, [
                'label' => 'Premier jour travaillé',
                'widget' => 'single_text',
                'data' => new \DateTime(),
            ])
            ->add('endedAtTime', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Heure de reprise',
                'choices' => [
                    '08:00' => '08:00',
                    '12:00' => '12:00',
                ],
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                /** @var Period $period */
                $period = $event->getData();
                $form = $event->getForm();

                $startedAt = $period->getStartedAt();
                $endedAt = $period->getEndedAt();

                if ($startedAt instanceof \DateTimeImmutable) {
                    $startedAtTime = $form->get('startedAtTime')->getData();
                    if ($startedAtTime) {
                        [$hours, $minutes] = explode(':', $startedAtTime);
                        $period->setStartedAt($startedAt->setTime((int) $hours, (int) $minutes));
                    }
                }

                if ($endedAt instanceof \DateTimeImmutable) {
                    $endedAtTime = $form->get('endedAtTime')->getData();
                    if ($endedAtTime) {
                        [$hours, $minutes] = explode(':', $endedAtTime);
                        $period->setEndedAt($endedAt->setTime((int) $hours, (int) $minutes));
                    }
                }
            })
            ->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
                $period = $event->getData();
                $form = $event->getForm();

                if (!$period instanceof Period) {
                    return;
                }

                if ($period->getStartedAt()) {
                    $form->get('startedAtTime')->setData($period->getStartedAt()->format('H:i'));
                }

                if ($period->getEndedAt()) {
                    $form->get('endedAtTime')->setData($period->getEndedAt()->format('H:i'));
                }
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Period::class,
            'block_prefix' => 'period_picker',
        ]);
    }
}
