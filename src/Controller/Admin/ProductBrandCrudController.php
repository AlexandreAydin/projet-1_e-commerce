<?php

namespace App\Controller\Admin;

use App\Entity\ProductBrand;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProductBrandCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductBrand::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
  
            TextField::new('name'),
            SlugField::new('slug')->setTargetFieldName('name'),
       
        ];
    }
}
