<?php

namespace App\Form;

use App\Entity\Face;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire minimal pour éditer uniquement l'état d'une face (utilisé dans l'édition du panneau).
 */
class FaceEtatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('etat', ChoiceType::class, [
            'label' => false,
            'choices' => [
                'Excellent' => 'excellent',
                'Bon' => 'bon',
                'Moyen' => 'moyen',
                'Mauvais' => 'mauvais',
                'Hors service' => 'hors_service',
            ],
            'placeholder' => 'État',
            'attr' => ['class' => 'form-select form-select-sm'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Face::class,
        ]);
    }
}
