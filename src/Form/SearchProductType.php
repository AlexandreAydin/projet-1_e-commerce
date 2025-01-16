<?php

namespace App\Form;

use App\Entity\BrandModel;
use App\Entity\Categorie;
use App\Entity\ProductBrand;
use App\Entity\SubCategorie;
use App\Repository\BrandModelRepository;
use App\Repository\SubCategorieRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchProductType extends AbstractType
{

    public function __construct(private SubCategorieRepository $subCategorieRepository, private BrandModelRepository $brandModelRepository) {} 
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $filteredCategories = $options['filtered_categories'] ?? [];
        $filteredBrands     = $options['filtered_brands'] ?? [];

        $builder
            ->add('categories', EntityType::class, [
                'class' => Categorie::class,
                'choices'  => $filteredCategories,
                'multiple' => true,
                'expanded' => true, // Checkboxes
                'required' => false,
                'attr' => ['class' => 'js-categories'], // Classe JS pour interaction
            ])
            ->add('subCategories', EntityType::class, [
                'class' => SubCategorie::class,
                'choice_label' => 'name',
                "label" => '',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'attr' => ['class' => 'js-subcategories'], // Classe JS pour interaction dynamique
                'choices' => $this->subCategorieRepository->findAll(),
            ])
            ->add('productBrand', EntityType::class, [
                'class' => ProductBrand::class,
                'choices'  => $filteredBrands,
                'multiple' => true,
                'expanded' => true, 
                'required' => false,
                'attr' => ['class' => 'productBrand'], // Classe JS pour interaction
            ])
            ->add('brandModel', EntityType::class, [
                'class' => BrandModel::class,
                'choice_label' => 'name',
                "label" => '',
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'attr' => ['class' => 'js-brandModel'], // Classe JS pour interaction dynamique
                'choices' => $this->brandModelRepository->findAll(),
            ])
            ->add('minPrice', IntegerType::class, [
                'required' => false,
                'label' => false,
                'attr' => ['placeholder' => 'Min Prix'],
            ])
            ->add('maxPrice', IntegerType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Max Prix'],
            ]);
    }
    
    

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Si vous avez un data_class pour ce formulaire, par ex. :
            // 'data_class' => SearchProduct::class,
    
            // On définit par défaut les deux options personnalisées :
            'filtered_categories' => [],
            'filtered_brands'     => [],
        ]);
    }
}
