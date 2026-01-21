<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Face;
use App\Entity\Location;
use App\Repository\ClientRepository;
use App\Repository\FaceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('face', EntityType::class, [
                'class' => Face::class,
                'choice_label' => function (Face $face) {
                    return $face->getNomComplet() . ' - ' . $face->getPanneau()->getEmplacement() . 
                           ($face->getLocationActive() ? ' (Occupée)' : ' (Disponible)');
                },
                'label' => 'Face du panneau',
                'placeholder' => 'Sélectionner une face',
                'query_builder' => function (FaceRepository $er) {
                    return $er->createQueryBuilder('f')
                        ->orderBy('f.lettre', 'ASC');
                },
                'group_by' => function (Face $face) {
                    return $face->getPanneau()->getReference();
                }
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'nom',
                'label' => 'Client',
                'placeholder' => 'Sélectionner un client',
                'query_builder' => function (ClientRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.nom', 'ASC');
                }
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('montantMensuel', MoneyType::class, [
                'label' => 'Montant mensuel (FCFA)',
                'currency' => 'XOF',
                'divisor' => 1,
                'attr' => ['placeholder' => '150000']
            ])
            ->add('estPaye', CheckboxType::class, [
                'label' => 'Paiement effectué',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['rows' => 4]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
        ]);
    }
}
