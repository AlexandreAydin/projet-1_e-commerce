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
            // Si vous voulez ajouter un champ pour sélectionner la commande liée, vous pouvez le faire ici.
            // Sinon, vous pouvez le supprimer de ce formulaire.
            // ->add('orders', EntityType::class, [
            //     'class' => Order::class,
            //     'choice_label' => 'id', // ou tout autre champ que vous souhaitez afficher
            //     'label' => 'Commande'
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => OrderDetails::class,
        ]);
    }
}
