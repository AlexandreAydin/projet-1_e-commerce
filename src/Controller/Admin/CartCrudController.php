<?php

namespace App\Controller\Admin;

use App\Entity\Cart;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CartCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Cart::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
       return $crud->setDefaultSort(['id'=>'DESC']);
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('user.FullName', 'Client'),
            TextField::new('CarrierName', 'Nom de Livreur'),
            MoneyField::new('CarrierPrice','Expédition')->setCurrency('EUR'),
            MoneyField::new('subTotalHT','Sous TotalHT')->setCurrency('EUR'),
            MoneyField::new('Taxe','TVA')->setCurrency('EUR'),
            MoneyField::new('subTotalTTC','sousTotalTTC')->setCurrency('EUR'),
            BooleanField::new('isPaid','Commande payer')
        ];
    }
    
}
