<?php

namespace App\Form;

use App\Entity\Carrier;
use App\Entity\Address;  // Make sure you import your Address entity too.
use Symfony\Bridge\Doctrine\Form\Type\EntityType;  // Correct EntityType import
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $builder
            ->add('address', EntityType::class,[
                'class'=> Address::class,
                'required'=>true,
                'choices'=>$user->getAddresses(),
                'multiple'=>false,
                'expanded'=>true,
            ])
            ->add('carrier', EntityType::class,[
                'class'=> Carrier::class,
                'required'=>true,
                'multiple'=>false,
                'expanded'=>true,
            ])
            ->add('information', TextareaType::class,[
                'required'=>false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'user' => array()
        ]);

    }
}
