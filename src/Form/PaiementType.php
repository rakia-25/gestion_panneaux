<?php

namespace App\Form;

use App\Entity\Location;
use App\Entity\Paiement;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaiementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $location = $options['location'] ?? null;

        $builder
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => function (Location $location) {
                    return $location->getFace()->getNomComplet() . ' - ' . $location->getClient()->getNom() .
                           ' (' . $location->getDateDebut()->format('d/m/Y') . ' - ' . $location->getDateFin()->format('d/m/Y') . ')';
                },
                'label' => 'Location',
                'placeholder' => 'Sélectionner une location',
                'attr' => [
                    'id' => 'paiement_location',
                ],
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant (FCFA)',
                'currency' => 'XOF',
                'divisor' => 1,
                'attr' => [
                    'placeholder' => '150000',
                    'id' => 'paiement_montant',
                    'type' => 'number',
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]*',
                    'min' => '0',
                    'step' => '1',
                ],
            ])
            ->add('datePaiement', DateType::class, [
                'label' => 'Date de paiement',
                'widget' => 'single_text',
                'html5' => true,
                'data' => new \DateTime(),
                'attr' => [
                    'id' => 'paiement_datePaiement',
                    'max' => (new \DateTime())->format('Y-m-d'),
                ],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de paiement',
                'choices' => [
                    'Acompte' => 'acompte',
                    'Solde' => 'solde',
                    'Paiement complet' => 'paiement_complet',
                    'Autre' => 'autre',
                ],
                'placeholder' => 'Sélectionner un type',
                'attr' => [
                    'id' => 'paiement_type',
                    'class' => 'form-select',
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Notes sur ce paiement (optionnel)',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Paiement::class,
            'location' => null,
        ]);
    }
}
