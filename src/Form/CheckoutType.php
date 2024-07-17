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
            ->add('address', EntityType::class,[
                'class'=> Address::class,
                'required'=>true,
                'choices'=>$user->getAddresses(),
                'multiple'=>false,
                'expanded'=>true,
            ])
            ->add('carrier', EntityType::class, [
                'class' => Carrier::class,
                'expanded' => true,
                'multiple' => false,
                'required' => true,
                'query_builder' => function (EntityRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('c');
                    if ($options['is_free_delivery_available']) {
                        $qb->where('c.id = 2');
                    } else {
                        $qb->where('c.id != 2');
                    }
                    return $qb;
                },
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
