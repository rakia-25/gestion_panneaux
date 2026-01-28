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
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PaiementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Location|null $location */
        $location = $options['location'] ?? null;
        $locationPreselectionnee = $location !== null;
        /** @var Paiement|null $currentPaiement */
        $currentPaiement = $options['data'] ?? null;
        $currentLocationId = $currentPaiement && $currentPaiement->getLocation()
            ? $currentPaiement->getLocation()->getId()
            : null;

        $builder
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => function (Location $location) {
                    return $location->getFace()->getNomComplet() . ' - ' . $location->getClient()->getNom() .
                           ' (' . $location->getDateDebut()->format('d/m/Y') . ' - ' . $location->getDateFin()->format('d/m/Y') . ')';
                },
                'label' => 'Location',
                'placeholder' => 'Sélectionner une location',
                'disabled' => $locationPreselectionnee,
                'query_builder' => function ($er) use ($location, $currentLocationId, $locationPreselectionnee) {
                    $qb = $er->createQueryBuilder('l');
                    
                    // Exclure les locations annulées (sauf si c'est la location actuelle en édition)
                    if ($locationPreselectionnee && $location) {
                        // Si la location est présélectionnée, limiter à cette location
                        // (le contrôleur vérifie déjà qu'elle est valide)
                        $qb->where('l.id = :locationId')
                           ->setParameter('locationId', $location->getId());
                    } elseif ($currentLocationId) {
                        // En édition, limiter à la location actuelle (même si annulée, pour consultation)
                        $qb->where('l.id = :currentLocationId')
                           ->setParameter('currentLocationId', $currentLocationId);
                    } else {
                        // Pour les nouvelles sélections, exclure les locations annulées
                        $qb->where('l.statut != :annulee')
                           ->setParameter('annulee', 'annulee');
                    }
                    
                    return $qb->orderBy('l.dateDebut', 'DESC');
                },
                'attr' => [
                    'id' => 'paiement_location',
                ],
            ])
            ->add('montant', MoneyType::class, [
                'label' => 'Montant (FCFA)',
                'currency' => 'XOF',
                'divisor' => 1,
                'scale' => 0, // affichage sans décimales
                'attr' => [
                    'placeholder' => '150000',
                    'id' => 'paiement_montant',
                    'type' => 'number',
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]*',
                    'min' => '0',
                    'step' => '1',
                ],
                'constraints' => [
                    new Callback([
                        'callback' => function ($value, ExecutionContextInterface $context) use ($location, $currentPaiement) {
                            if ($value && $location) {
                                $montantPaiement = floatval($value);
                                
                                // En édition, exclure le montant du paiement actuel du calcul
                                if ($currentPaiement && $currentPaiement->getId()) {
                                    $ancienMontant = floatval($currentPaiement->getMontant());
                                    $montantTotalPaye = floatval($location->getMontantTotalPaye());
                                    $montantTotalPayeSansActuel = $montantTotalPaye - $ancienMontant;
                                    $montantTotal = floatval($location->getMontantTotal());
                                    $montantRestant = $montantTotal - $montantTotalPayeSansActuel;
                                } else {
                                    $montantRestant = floatval($location->getMontantRestant());
                                }
                                
                                if ($montantPaiement > $montantRestant) {
                                    $context->buildViolation(sprintf(
                                        'Le montant du paiement (%.0f FCFA) ne peut pas dépasser le montant restant à payer (%.0f FCFA).',
                                        $montantPaiement,
                                        $montantRestant
                                    ))->addViolation();
                                }
                            }
                        }
                    ])
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

