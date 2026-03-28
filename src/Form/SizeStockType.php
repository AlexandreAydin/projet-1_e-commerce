<?php

namespace App\Form;

use App\Entity\SizeStock;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType as TypeIntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SizeStockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('size', TextType::class, [
            'label' => 'Taille',
            'required' => true,
        ])
        ->add('stock', TypeIntegerType::class, [
            'label' => 'Stock',
            'required' => true,
            'required' => true, // Ensures the field is not empty
            'empty_data' => '0', // Fallback if no value is provided
        ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SizeStock::class,
        ]);
    }
}
