<?php

// src/Form/RewiewsProductType.php

namespace App\Form;

use App\Entity\RewiewsProduct;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RewiewsProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('note', IntegerType::class, [
                'label' => 'Rating',
                'required' => true,
            ])
            ->add('comment', TextareaType::class, [
                'label' => 'Votre commentaire',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Votre commentaire *',
                    'rows' => 4,
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Submit Review',
                'attr' => [
                    'class' => 'btn btn-fill-out'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RewiewsProduct::class,
        ]);
    }
}
