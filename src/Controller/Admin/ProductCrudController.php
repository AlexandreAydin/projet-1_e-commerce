<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use App\Form\ProductImageType;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Vich\UploaderBundle\Form\Type\VichFileType;

class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            SlugField::new('slug')->setTargetFieldName('name'),
            TextareaField::new('description'),
            TextareaField::new('moreInformations'),
            MoneyField::new('price')->setCurrency('USD'),
            IntegerField::new('quantity'),
            TextField::new('Tags'),
            BooleanField::new('isBestSeller'),
            BooleanField::new('isNewArrival'),
            BooleanField::new('isFeatured'),
            BooleanField::new('isSpacialOffer'),
            CollectionField::new('productImages')
            ->setFormType(CollectionType::class)
            ->setFormTypeOptions([
                'entry_type' => ProductImageType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => [
                    'label' => 'Image',
                    'download_uri' => true,
                    'required' => false,
                ],
            ])
            ,
            
            // CollectionField::new('productImages')
            // ->setFormType(ProductImageType::class)
            // ->setFormTypeOptions([
            //     'by_reference' => false,
            //     'allow_add' => true,
            //     'allow_delete' => true,
            //     'entry_options' => [
            //         'label' => 'Image',
            //         'required' => false,
            //         'download_label' => 'Télécharger',
            //         'download_uri' => true,
            //         'asset_helper' => true,
            //         'imagine_pattern' => 'thumb_filter',
            //     ],
            // ])
            // ->onlyOnForms(),
          
        ];
           
    }
    
}

