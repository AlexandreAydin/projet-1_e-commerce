<?php

namespace App\Form;

use App\Entity\OrderDetails;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderDetailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('productName', TextType::class, [
                'label' => 'Nom du produit'
            ])
            ->add('productPrice', NumberType::class, [
                'label' => 'Prix du produit'
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantité'
            ])
            ->add('subTotalHt', NumberType::class, [
                'label' => 'Sous Total HT'
            ])
            ->add('taxe', NumberType::class, [
                'label' => 'Taxe'
            ])
            ->add('subTotalTTC', NumberType::class, [
                'label' => 'Sous Total TTC'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OrderDetails::class,
        ]);
    }
}
