<?php

// src/Form/RewiewsProductType.php

namespace App\Form;

use App\Entity\RewiewsProduct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RewiewsProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('note', IntegerType::class, [
                'label' => 'Rating',
                'required' => true,
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Votre commentaire',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre commentaire *',
                    'rows' => 4,
                ]
            ])
            ->add('rewiewImage', FileType::class, [
                'label' => 'Image',
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'mt-2'
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('rewiewImages2', FileType::class, [
                'label' => 'Images 2',
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'mt-2'
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('rewiewImages3', FileType::class, [
                'label' => 'Images 3',
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'mt-2'
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('rewiewImages4', FileType::class, [
                'label' => 'Images 4',
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'mt-2'
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('rewiewImages5', FileType::class, [
                'label' => 'Images 5',
                'attr' => [
                    'accept' => 'image/*',
                    'class' => 'mt-2'
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('reviewVideo', FileType::class, [
                'label' => 'Video',
                'attr' => [
                    'accept' => 'video/*'
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Publier',
                'attr' => [
                    'class' => 'btn btn-fill-out'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RewiewsProduct::class,
        ]);
    }
}
