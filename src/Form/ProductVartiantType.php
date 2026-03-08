<?php
// src/Form/ProductVariantType.php
// Ajoute uniquement le champ colorHex — garde tout le reste intact.
// Si tu as déjà d'autres champs dans ce formulaire, ajoute juste le bloc colorHex ci-dessous.

namespace App\Form;

use App\Entity\ProductVariant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use App\Form\ProductImageType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductVariantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('color', TextType::class, [
                'label' => 'Couleur (nom)',
                'required' => false,
            ])
            // ── Champ color picker natif (clic = sélecteur de couleur) ────
            ->add('colorHex', ColorType::class, [
                'label'    => 'Couleur (cliquez pour choisir)',
                'required' => false,
                'attr'     => [
                    'class' => 'form-control form-control-color',
                    'style' => 'width:60px; height:40px; padding:2px; cursor:pointer;',
                    'title' => 'Choisir une couleur',
                ],
            ])
            ->add('ean', TextType::class, [
                'label'    => 'EAN / Code-barres',
                'required' => false,
                'attr'     => ['placeholder' => 'ex: 3760123456789'],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix (€)',
            ])
            ->add('offVariant', IntegerType::class, [
                'label'    => 'Réduction (%)',
                'required' => false,
                'data'     => 0,
            ])
            ->add('sizes', CollectionType::class, [
                'entry_type'   => SizeStockType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label'        => 'Tailles / Stock',
            ])
            ->add('variantImages', CollectionType::class, [
                'entry_type'   => ProductImageType::class,
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype'    => true,
                'entry_options' => ['label' => false],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductVariant::class,
        ]);
    }
}