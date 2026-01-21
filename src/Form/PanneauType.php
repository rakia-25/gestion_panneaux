<?php

namespace App\Form;

use App\Entity\Panneau;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
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
                'label' => 'Emplacement',
                'attr' => ['placeholder' => 'Adresse à Niamey']
            ])
            ->add('taille', TextType::class, [
                'label' => 'Taille',
                'attr' => ['placeholder' => 'Ex: 4x3m']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Simple' => 'simple',
                    'Double' => 'double',
                ],
                'placeholder' => 'Sélectionner un type'
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
