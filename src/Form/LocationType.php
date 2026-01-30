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
        /** @var Location|null $currentLocation */
        $currentLocation = $options['data'] ?? null;
        $currentFaceId = $currentLocation && $currentLocation->getFace() ? $currentLocation->getFace()->getId() : null;
        $facePreselectionnee = $options['face_preselectionnee'] ?? false;
        $clientPreselectionne = $options['client_preselectionne'] ?? false;
        
        $builder
            ->add('face', EntityType::class, [
                'class' => Face::class,
                'choice_label' => function (Face $face) {
                    return $face->getNomComplet() . ' - ' . $face->getPanneau()->getEmplacement();
                },
                'label' => 'Face du panneau',
                'placeholder' => 'Sélectionner une face',
                'disabled' => $facePreselectionnee,
                'choice_attr' => function (Face $face) {
                    return ['data-etat' => $face->getEtat() ?? ''];
                },
                'query_builder' => function (FaceRepository $er) use ($currentFaceId, $facePreselectionnee, $currentLocation) {
                    $qb = $er->createQueryBuilder('f')
                        ->leftJoin('f.panneau', 'p');

                    if ($currentFaceId) {
                        $qb->where('f.id = :currentFaceId')
                           ->setParameter('currentFaceId', $currentFaceId);
                    } elseif ($facePreselectionnee && $currentLocation && $currentLocation->getFace()) {
                        $facePreselectionneeId = $currentLocation->getFace()->getId();
                        $qb->where('f.id = :facePreselectionneeId')
                           ->setParameter('facePreselectionneeId', $facePreselectionneeId);
                    } else {
                        // Panneaux actifs ; faces louables (exclut les faces hors service)
                        $qb->where('p.actif = :actif')
                           ->andWhere('f.etat != :horsService')
                           ->setParameter('actif', true)
                           ->setParameter('horsService', 'hors_service');
                    }

                    return $qb->orderBy('p.reference', 'ASC')
                              ->addOrderBy('f.lettre', 'ASC');
                },
                'group_by' => function (Face $face) {
                    return $face->getPanneau()->getReference();
                },
            ])
            ->add('confirmerEtatMauvais', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Je confirme vouloir louer ce panneau malgré son état dégradé',
                'attr' => ['id' => 'location_confirmerEtatMauvais'],
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'nom',
                'label' => 'Client',
                'placeholder' => 'Sélectionner un client',
                // En création avec client_id, le client est présélectionné et non modifiable
                'disabled' => $clientPreselectionne,
                'query_builder' => function (ClientRepository $er) use ($currentLocation, $clientPreselectionne) {
                    $qb = $er->createQueryBuilder('c')->orderBy('c.nom', 'ASC');
                    if ($clientPreselectionne && $currentLocation && $currentLocation->getClient()) {
                        $qb->where('c.id = :clientId')->setParameter('clientId', $currentLocation->getClient()->getId());
                    }
                    return $qb;
                },
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
                    'type' => 'number',
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]*',
                    'min' => 1,
                    'max' => 120,
                    'step' => '1',
                    'placeholder' => 'Ex: 3',
                ],
                'help' => 'La date de fin sera calculée automatiquement. Ex: 3 mois à partir du 23/01/2026 = 23/04/2026'
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'html5' => true,
                'disabled' => true, // Toujours désactivé car calculée automatiquement
                'attr' => [
                    'id' => 'location_dateFin',
                ],
                'help' => 'Calculée automatiquement en fonction de la date de début et de la durée.'
            ])
            ->add('montantMensuel', MoneyType::class, [
                'label' => 'Montant mensuel (FCFA)',
                'currency' => 'XOF',
                'divisor' => 1,
                'scale' => 0, // affichage sans décimales
                'attr' => [
                    'placeholder' => '150000',
                    'id' => 'location_montantMensuel',
                    'type' => 'number',
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]*',
                    'min' => '0',
                    'step' => '1',
                    // Prix original injecté pour JS (si édition)
                    'data-original-price' => $currentLocation && $currentLocation->getMontantMensuel()
                        ? $currentLocation->getMontantMensuel()
                        : '',
                ],
                'help' => 'Le montant sera pré-rempli automatiquement depuis le panneau sélectionné. Vous pouvez le modifier si nécessaire.'
            ])
            ->add('notes', ChoiceType::class, [
                'label' => 'Justification (si prix modifié)',
                'required' => false,
                'placeholder' => 'Sélectionner une justification',
                'choices' => [
                    'Remise pour fidélité client' => 'Remise pour fidélité client',
                    'Remise pour location longue durée' => 'Remise pour location longue durée',
                    'Remise pour location courte durée' => 'Remise pour location courte durée',
                    'Remise commerciale' => 'Remise commerciale',
                    'Tarif spécial événement' => 'Tarif spécial événement',
                    'Majoration pour emplacement premium' => 'Majoration pour emplacement premium',
                    'Majoration pour période de pointe' => 'Majoration pour période de pointe',
                    'Autre (préciser dans les notes)' => 'Autre',
                ],
                'attr' => [
                    'id' => 'location_notes',
                    'class' => 'form-select',
                ],
                'help' => 'Ce champ devient obligatoire si le prix est modifié.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
            'face_preselectionnee' => false,
            'client_preselectionne' => false,
        ]);
    }
}

