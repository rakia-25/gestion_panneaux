<?php

namespace App\Form;

use App\Entity\Panneau;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $panneau = $event->getData();
            if ($panneau && $panneau->getId() !== null && $panneau->getFaces()->count() > 0) {
                $event->getForm()->add('faces', CollectionType::class, [
                    'entry_type' => FaceEtatType::class,
                    'entry_options' => ['label' => false],
                    'by_reference' => false,
                    'label' => 'État de chaque face',
                    'help' => 'Sur un panneau double, chaque face peut avoir son propre état. Passez le type à "Double" pour afficher la face B.',
                ]);
                // Panneau actuellement simple : champ pour créer la face B si l'utilisateur passe en double
                if ($panneau->getFaces()->count() === 1) {
                    $event->getForm()->add('etatFaceB', ChoiceType::class, [
                        'mapped' => false,
                        'label' => 'État face B',
                        'choices' => [
                            'Excellent' => 'excellent',
                            'Bon' => 'bon',
                            'Moyen' => 'moyen',
                            'Mauvais' => 'mauvais',
                            'Hors service' => 'hors_service',
                        ],
                        'data' => 'bon',
                        'help' => 'Sera utilisé pour la face B si vous passez le panneau en type double.',
                    ]);
                }
            }
        });

        $builder
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'required' => false,
                // Référence jamais modifiable (générée automatiquement)
                'disabled' => true,
                'attr' => [
                    'placeholder' => 'Générée automatiquement',
                    'readonly' => true,
                ],
                'help' => $isNew
                    ? 'La référence sera générée automatiquement lors de la création.'
                    : 'Référence du panneau (non modifiable).'
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
            ]);

        if ($isNew) {
            $etatChoices = [
                'Excellent' => 'excellent',
                'Bon' => 'bon',
                'Moyen' => 'moyen',
                'Mauvais' => 'mauvais',
                'Hors service' => 'hors_service',
            ];
            $builder
                ->add('etatFaceA', ChoiceType::class, [
                    'mapped' => false,
                    'label' => 'État face A',
                    'choices' => $etatChoices,
                    'data' => 'bon',
                    'help' => 'État de la face A du panneau.',
                ])
                ->add('etatFaceB', ChoiceType::class, [
                    'mapped' => false,
                    'label' => 'État face B',
                    'choices' => $etatChoices,
                    'data' => 'bon',
                    'help' => 'Pour un panneau double uniquement. Ignoré si panneau simple.',
                ]);
        }

        $builder
            ->add('prixMensuel', MoneyType::class, [
                'label' => 'Prix mensuel (FCFA)',
                'currency' => 'XOF',
                'divisor' => 1,
                'scale' => 0, // affichage sans décimales
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

