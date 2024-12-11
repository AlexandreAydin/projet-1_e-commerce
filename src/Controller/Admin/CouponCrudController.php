<?php

namespace App\Controller\Admin;

use App\Entity\Coupon;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;

class CouponCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Coupon::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('code', 'Code du coupon'),
            NumberField::new('discountAmount', 'Montant de réduction'),
            DateTimeField::new('expirationDate', 'Date d\'expiration')->setRequired(false),
            BooleanField::new('isActive', 'Actif'),
            AssociationField::new('products', 'Produits')->setRequired(false)->setFormTypeOptions([
                'by_reference' => false,
            ]),
            AssociationField::new('categories', 'Catégories')->setRequired(false)->setFormTypeOptions([
                'by_reference' => false,
            ]),
            AssociationField::new('orders', 'Commandes')->onlyOnDetail(),
        ];
    }
}
