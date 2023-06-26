<?php

namespace App\Form;

use App\Entity\Categorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categories',EntityType::class,[
                'class'=>Categorie::class,
                'label'=> false,
                'required'=> false,
                'multiple'=>true,
                'attr'=>[
                    'class'=> 'js-categories-multiple'
                ]
            ])
            ->add('minPrice', IntegerType::class,[
                'required'=> false,
                'label'=> false,
                'attr'=>[
                    'placeholder'=>'min...'
                ]
            ])
            ->add('maxPrice', IntegerType::class,[
                'required'=> false,
                'label'=> false,
                'attr'=>[
                    'placeholder'=>'max...'
                ]
            ])
            ->add('tags',TextType::class,[
                'label'=>false,
                'required'=>false,
                'attr'=> [
                    'placeholder' => "Tags..."
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
