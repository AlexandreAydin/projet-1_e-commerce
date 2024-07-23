<?php

namespace App\Form;

use App\Entity\Carrier;
use App\Entity\Address;  // Make sure you import your Address entity too.
use Doctrine\ORM\EntityRepository;
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
        ->add('address', EntityType::class, [
            'class' => Address::class,
            'choice_label' => function (Address $address) {
                return  $address->getfullName() . ', ' . $address->getAddress() . ', ' . $address->getCity() . ', ' . $address->getCodePostal();
            },
            'required' => true,
            'placeholder' => 'Choose an address',
            'multiple' => false,
            'expanded' => false, 
            'query_builder' => function ($repo) use ($user) {
                return $repo->createQueryBuilder('a')
                    ->where('a.user = :user')
                    ->setParameter('user', $user);
            },
        ])
        ->add('billingAddress', EntityType::class, [
            'class' => Address::class,
            'choice_label' => function (Address $address) {
                return  $address->getFullName() . ', ' . $address->getAddress() . ', ' . $address->getCity() . ', ' . $address->getCodePostal();
            },
            'required' => true,
            'placeholder' => 'Choose an address',
            'multiple' => false,
            'expanded' => false, 
            'query_builder' => function ($repo) use ($user) {
                return $repo->createQueryBuilder('a')
                    ->where('a.user = :user')
                    ->setParameter('user', $user);
            },
        ])
        ->add('carrier', EntityType::class, [
            'class' => Carrier::class,
            'expanded' => true,
            'multiple' => false,
            'required' => true,
        ])
        ->add('information', TextareaType::class,[
            'required'=>false,
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'user' => null,
            'is_free_delivery_available' => false,
        ]);
    }
}
