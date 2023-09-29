<?php

namespace App\Controller\Admin;

use App\Entity\OrderDetails;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\CrudDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use phpDocumentor\Reflection\Types\Integer;

class OrderDetailsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return OrderDetails::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
       return $crud->setDefaultSort(['id'=>'DESC']);
    }


    
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id', 'ID'),
            AssociationField::new('orders', 'Commande ID'),
            TextField::new('productName', 'Product Name'),
            IntegerField::new('quantity', 'Quantity'), // Utilisez IntegerField ici
            MoneyField::new('subtotalHT', 'Sub Total HT')->setCurrency('EUR'),
            MoneyField::new('taxe', 'Taxe')->setCurrency('EUR'),
            MoneyField::new('subTotalTTC', 'Sub Total TTC')->setCurrency('EUR')
            
    ];
    }
    
}
