<?php

namespace App\Controller\Admin;

use App\Entity\RewiewsProduct;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class RewiewsProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RewiewsProduct::class;
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
