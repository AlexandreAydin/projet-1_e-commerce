<?php

namespace App\Form;

use App\Entity\Address;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddressType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('fullName', TextType::class, [
            'label' => 'Nom et Prénom *',
            'required' => true,
        ])
        ->add('campany', TextType::class, [
            'label' => 'Raison sociale',
            'required' => false,
        ])
        ->add('address', TextType::class, [
            'label' => 'Adresse *',
            'required' => true,
        ])
        ->add('complement', TextType::class, [
            'label' => "Complément d'adresse",
            'required' => false,
        ])
        ->add('phone', NumberType::class, [
            'label' => "Téléphone *",
            'required' => true,
        ])
        ->add('city', TextType::class, [
            'label' => "Ville *",
            'required' => true,
        ])
        ->add('codePostal', NumberType::class, [
            'label' => "Code Postal *",
            'required' => true,
        ])
        ->add('country', CountryType::class, [
            'label' => "Pays *",
            'preferred_choices' => ['FR'], // Set France as the preferred choice
            'data' => 'FR', // Set France as the default value
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
        ]);
    }
}
