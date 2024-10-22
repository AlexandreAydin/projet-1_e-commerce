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
use App\Form\ProductVariantType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\Extension\Core\Type\TextType;


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
            CollectionField::new('productVariants')
                ->setEntryType(ProductVariantType::class) // Utilisez ProductVariantType
                ->setFormTypeOptions([
                    'by_reference' => false,
                ])
                ->allowAdd(true)
                ->allowDelete(true)
                ->setEntryIsComplex(true)
                ->onlyOnForms(),
            MoneyField::new('price')->setCurrency('EUR'),
            IntegerField::new('off','reduction en %'),
            IntegerField::new('quantity'),
            BooleanField::new('isBestSeller'),
            BooleanField::new('isNewArrival'),
            BooleanField::new('isFeatured'),
            BooleanField::new('isSpacialOffer'),
            // CollectionField::new('sizes')
            // ->setEntryType(TextType::class)   // Chaque entrée sera un champ de texte pour une taille
            // ->allowAdd(true)                  // Autoriser l'ajout de nouvelles tailles
            // ->allowDelete(true)               // Autoriser la suppression de tailles
            // ->setLabel('Tailles'),
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

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Product) {
            foreach ($entityInstance->getProductVariants() as $productVariant) {
                $productVariant->setProduct($entityInstance);
                foreach ($productVariant->getVariantImages() as $variantImage) {
                    // Lier l'image au produit via la variante
                    $variantImage->setProduct($entityInstance);
                }
            }
        }
    
        parent::persistEntity($entityManager, $entityInstance);
    }
    
    
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Product) {
            foreach ($entityInstance->getProductVariants() as $productVariant) {
                // Liez le produit à chaque variante
                $productVariant->setProduct($entityInstance);
            }
        }
    
        parent::updateEntity($entityManager, $entityInstance);
    }
    




    
}