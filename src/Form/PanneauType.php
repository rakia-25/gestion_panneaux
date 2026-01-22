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
use Symfony\Component\Validator\Constraints\File;

class PanneauType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reference', TextType::class, [
                'label' => 'Référence',
                'attr' => ['placeholder' => 'Ex: PAN-001']
            ])
            ->add('emplacement', TextType::class, [
                'label' => 'Emplacement principal',
                'attr' => ['placeholder' => 'Adresse principale à Niamey']
            ])
            ->add('quartier', TextType::class, [
                'label' => 'Quartier',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Plateau, Terminus, etc.']
            ])
            ->add('rue', TextType::class, [
                'label' => 'Rue / Avenue',
                'required' => false,
                'attr' => ['placeholder' => 'Nom de la rue ou avenue']
            ])
            ->add('coordonneesGps', TextType::class, [
                'label' => 'Coordonnées GPS',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: 13.5123,2.1098'],
                'help' => 'Format: latitude,longitude'
            ])
            ->add('taille', NumberType::class, [
                'label' => 'Taille (m²)',
                'scale' => 2,
                'attr' => [
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
                'attr' => ['placeholder' => '150000']
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
