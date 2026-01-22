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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentLocation = $options['data'] ?? null;
        $currentFaceId = $currentLocation && $currentLocation->getFace() ? $currentLocation->getFace()->getId() : null;
        
        $builder
            ->add('face', EntityType::class, [
                'class' => Face::class,
                'choice_label' => function (Face $face) {
                    return $face->getNomComplet() . ' - ' . $face->getPanneau()->getEmplacement();
                },
                'label' => 'Face du panneau',
                'placeholder' => 'Sélectionner une face',
                'query_builder' => function (FaceRepository $er) use ($currentFaceId) {
                    $now = new \DateTime();
                    $qb = $er->createQueryBuilder('f')
                        ->leftJoin('f.locations', 'l', 'WITH', 'l.dateFin >= :now')
                        ->where('l.id IS NULL');
                    
                    // Si on édite une location, inclure aussi la face actuelle
                    if ($currentFaceId) {
                        $qb->orWhere('f.id = :currentFaceId')
                           ->setParameter('currentFaceId', $currentFaceId);
                    }
                    
                    return $qb->setParameter('now', $now)
                        ->groupBy('f.id')
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
                'attr' => [
                    'id' => 'location_dateDebut',
                    'min' => (new \DateTime())->format('Y-m-d'),
                ],
                'help' => 'La date de début ne peut pas être antérieure à aujourd\'hui.'
            ])
            ->add('dureeMois', IntegerType::class, [
                'label' => 'Durée (en mois)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'id' => 'location_dureeMois',
                    'min' => 1,
                    'max' => 120,
                    'placeholder' => 'Ex: 3',
                ],
                'help' => 'La date de fin sera calculée automatiquement. Ex: 3 mois à partir du 23/01/2026 = 23/04/2026'
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'id' => 'location_dateFin',
                    'readonly' => true,
                    'disabled' => false, // Ne pas utiliser disabled car cela empêche la soumission du formulaire
                ],
                'help' => 'Calculée automatiquement en fonction de la date de début et de la durée.'
            ])
            ->add('montantMensuel', MoneyType::class, [
                'label' => 'Montant mensuel (FCFA)',
                'currency' => 'XOF',
                'divisor' => 1,
                'attr' => [
                    'placeholder' => '150000',
                    'id' => 'location_montantMensuel',
                ],
                'help' => 'Le montant sera pré-rempli automatiquement depuis le panneau sélectionné. Vous pouvez le modifier si nécessaire.'
            ])
            ->add('estPaye', CheckboxType::class, [
                'label' => 'Paiement effectué',
                'required' => false,
            ])
            ->add('notes', ChoiceType::class, [
                'label' => 'Justification (si prix modifié)',
                'required' => false,
                'placeholder' => 'Sélectionner une justification',
                'choices' => [
                    'Remise pour fidélité client' => 'Remise pour fidélité client',
                    'Remise pour location longue durée' => 'Remise pour location longue durée',
                    'Remise commerciale' => 'Remise commerciale',
                    'Tarif spécial événement' => 'Tarif spécial événement',
                    'Majoration pour emplacement premium' => 'Majoration pour emplacement premium',
                    'Majoration pour période de pointe' => 'Majoration pour période de pointe',
                    'Autre (préciser dans les notes)' => 'Autre',
                ],
                'attr' => [
                    'id' => 'location_notes',
                    'class' => 'form-select'
                ],
                'help' => 'Ce champ devient obligatoire si le prix est modifié.'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
        ]);
    }
}
