<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Form\ProductImageType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use FOS\CKEditorBundle\Form\Type\CKEditorType;


class ProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
    return $crud
        ->addFormTheme('@FOSCKEditor/Form/ckeditor_widget.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            SlugField::new('slug')->setTargetFieldName('name'),
            TextEditorField::new('description')
                ->setFormType(CKEditorType::class)
                ->hideOnIndex(),
            MoneyField::new('price')->setCurrency('EUR'),
            IntegerField::new('off','reduction en %'),
            IntegerField::new('quantity'),
            BooleanField::new('isBestSeller'),
            BooleanField::new('isNewArrival'),
            BooleanField::new('isFeatured'),
            BooleanField::new('isSpacialOffer'),
            CollectionField::new('images')
                ->setEntryType(ProductImageType::class),
            AssociationField::new('categorie'),
            TextEditorField::new('description2')
                    ->setFormType(CKEditorType::class)
                    ->hideOnIndex(),
            TextEditorField::new('illustrationText1')
            ->setFormType(CKEditorType::class)
            ->hideOnIndex(),
        ];
           
    }   
}