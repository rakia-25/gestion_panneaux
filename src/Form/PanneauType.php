<?php

namespace App\Form;

use App\Entity\Panneau;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PanneauType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $panneau = $options['data'] ?? null;
        $isNew = $panneau === null || $panneau->getId() === null;
        
        $builder
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'required' => false,
                'disabled' => $isNew, // Désactivé lors de la création (sera généré automatiquement)
                'attr' => [
                    'placeholder' => 'Générée automatiquement',
                    'readonly' => $isNew,
                ],
                'help' => $isNew ? 'La référence sera générée automatiquement lors de la création.' : 'Référence du panneau'
            ])
            ->add('emplacement', TextType::class, [
                'label' => 'Emplacement',
                'attr' => ['placeholder' => 'Ex: Avenue de la République, près du rond-point']
            ])
            ->add('quartier', TextType::class, [
                'label' => 'Quartier',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Plateau, Terminus, Centre-ville, Aéroport, etc.']
            ])
            ->add('visibilite', ChoiceType::class, [
                'label' => 'Visibilité',
                'required' => false,
                'choices' => [
                    'Excellente' => 'Excellente',
                    'Bonne' => 'Bonne',
                    'Moyenne' => 'Moyenne',
                    'Faible' => 'Faible',
                ],
                'placeholder' => 'Sélectionner une visibilité'
            ])
            ->add('coordonneesGps', TextType::class, [
                'label' => 'Coordonnées GPS (optionnel)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex: 13.5123,2.1098',
                    'pattern' => '^-?([0-8]?[0-9](\.[0-9]{1,6})?|90(\.0{1,6})?),-?([0-1]?[0-7]?[0-9](\.[0-9]{1,6})?|180(\.0{1,6})?)$',
                    'title' => 'Format requis: latitude,longitude (ex: 13.5123,2.1098). Latitude: -90 à 90, Longitude: -180 à 180',
                    'data-format' => 'gps'
                ],
                'help' => 'Format: latitude,longitude (ex: 13.5123,2.1098). Latitude: -90 à 90, Longitude: -180 à 180',
                'constraints' => [
                    new Callback([
                        'callback' => function ($value, ExecutionContextInterface $context) {
                            if ($value && !empty(trim($value))) {
                                $gpsPattern = '/^-?([0-8]?[0-9](\.[0-9]{1,6})?|90(\.0{1,6})?),-?([0-1]?[0-7]?[0-9](\.[0-9]{1,6})?|180(\.0{1,6})?)$/';
                                if (!preg_match($gpsPattern, trim($value))) {
                                    $context->buildViolation('Le format des coordonnées GPS est invalide. Format attendu: latitude,longitude (ex: 13.5123,2.1098)')
                                        ->addViolation();
                                } else {
                                    // Vérifier les valeurs numériques
                                    $parts = explode(',', trim($value));
                                    if (count($parts) === 2) {
                                        $lat = floatval(trim($parts[0]));
                                        $lon = floatval(trim($parts[1]));
                                        if ($lat < -90 || $lat > 90) {
                                            $context->buildViolation('La latitude doit être entre -90 et 90.')
                                                ->addViolation();
                                        }
                                        if ($lon < -180 || $lon > 180) {
                                            $context->buildViolation('La longitude doit être entre -180 et 180.')
                                                ->addViolation();
                                        }
                                    }
                                }
                            }
                        }
                    ])
                ]
            ])
            ->add('taille', NumberType::class, [
                'label' => 'Taille (m²)',
                'scale' => 2,
                'attr' => [
                    'type' => 'number',
                    'inputmode' => 'decimal',
                    'pattern' => '[0-9]+(\.[0-9]{1,2})?',
                    'placeholder' => '12.00',
                    'step' => '0.01',
                    'min' => '0'
                ],
                'help' => 'Surface en mètres carrés (ex: 12 m²)'
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Simple' => 'simple',
                    'Double' => 'double',
                ],
                'placeholder' => 'Sélectionner un type'
            ])
            ->add('eclairage', CheckboxType::class, [
                'label' => 'Éclairage',
                'required' => false,
                'help' => 'Cocher si le panneau est éclairé'
            ])
            ->add('etat', ChoiceType::class, [
                'label' => 'État du panneau',
                'choices' => [
                    'Excellent' => 'excellent',
                    'Bon' => 'bon',
                    'Moyen' => 'moyen',
                    'Mauvais' => 'mauvais',
                    'Hors service' => 'hors_service',
                ],
                'placeholder' => 'Sélectionner un état'
            ])
            ->add('prixMensuel', MoneyType::class, [
                'label' => 'Prix mensuel (FCFA)',
                'currency' => 'XOF',
                'divisor' => 1,
                'attr' => [
                    'placeholder' => '150000',
                    'type' => 'number',
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]*',
                    'min' => '0',
                    'step' => '1',
                ],
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG ou WebP)',
                    ])
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 4]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Panneau::class,
        ]);
    }
}
