<?php

namespace App\Controller\Admin;

use App\Entity\BrandModel;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class BrandModelCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return BrandModel::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            SlugField::new('slug')->setTargetFieldName('name'),
            AssociationField::new('productBrands')
                ->setQueryBuilder(function ($queryBuilder) {
                    return $queryBuilder
                        ->orderBy('entity.name', 'ASC'); // Remplacez 'entity.name' par le champ approprié si différent
            })
        ];
    }
}
