<?php

namespace App\Form;

use App\Entity\ProductVariant;
use App\Entity\SizeStock;
use App\Form\DataTransformer\SizeStockTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductVariantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            $productVariant = $event->getData(); // Get the ProductVariant entity
            foreach ($productVariant->getSizes() as $sizeStock) {
                $sizeStock->setProductVariant($productVariant); // Link each SizeStock to ProductVariant
            }})
            ->add('color', TextType::class)
            // ->add('size', TextType::class)
            // ->add('stock', IntegerType::class)
            ->add('sizes', CollectionType::class, [
                'entry_type' => SizeStockType::class, // Use the dedicated form type
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false, // Important for Doctrine collections
                'label' => 'Tailles et stocks',
            ])
            ->add('OffVariant', IntegerType::class, [
                'label' => 'Réduction en %', // Définir le label personnalisé ici
                'required' => false, // Optionnel, si le champ n'est pas obligatoire
            ])
            ->add('price', MoneyType::class, [
                'currency' => 'EUR',
            ])
            ->add('variantImages', CollectionType::class, [
                'entry_type' => ProductImageType::class,  // Utilise le formulaire ProductImageType pour chaque image
                'allow_add' => true,  // Autorise l'ajout d'images
                'allow_delete' => true,  // Autorise la suppression d'images
                'by_reference' => false,  // Important pour manipuler correctement les collections
                'prototype' => true,  // Permet l'ajout dynamique en JavaScript
                'entry_options' => ['label' => false],  // Désactiver le label pour chaque image
            ]);
    }


    
    

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductVariant::class,
        ]);
    }
}
