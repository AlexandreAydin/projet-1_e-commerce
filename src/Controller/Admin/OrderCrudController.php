<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Service\PdfService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use Doctrine\ORM\Mapping as ORM;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class OrderCrudController extends AbstractCrudController
{

    private $entityManager;
    private $adminUrlGenerator;
    private $pdfService;

    public function __construct(EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator, PdfService $pdfService)
    {
        $this->entityManager = $entityManager;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->pdfService = $pdfService;
    }

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $updatePreparation = Action::new('updatePreparation', 'Préparation en cours', 'fas fa-box-open')->linkToCrudAction('updatePreparation');
        $updateDelivery = Action::new('updateDelivery', 'Livraison en cours', 'fas fa-truck')->linkToCrudAction('updateDelivery');
        $delivery= Action::new('delivery','Livrée','fas fa-check')->linkToCrudAction('delivery');
        $printInvoice = Action::new('printInvoice', 'Imprimer', 'fa fa-print')->linkToCrudAction('printInvoice');

        return $actions
            ->add('detail', $updatePreparation)
            ->add('detail', $updateDelivery)
            ->add('detail', $delivery)
            ->add('index', 'detail')
            ->add('detail', $printInvoice);
    }

    public function printInvoice(AdminContext $context)
    {
        $order = $context->getEntity()->getInstance();
        
        // Vérifiez que la commande a des détails associés
        if (!$order->getOrderDetails() || $order->getOrderDetails()->isEmpty()) {
            $this->addFlash('error', "La commande " . $order->getReference() . " n'a pas de détails valides.");
        }
    
        // Récupérez le premier OrderDetail pour cette commande (si vous avez plusieurs détails, choisissez le bon ici)
        $firstOrderDetail = $order->getOrderDetails()->first();
    
        if (!$firstOrderDetail) {
            $this->addFlash('error', "Pas de détails valides trouvés pour la commande " . $order->getReference() . ".");
            // return $this->redirectToCrudIndex(); // Redirection vers la page de liste
        }
    
        // Générer l'URL pour la facture PDF
        $pdfUrl = $this->generateUrl('app_order_pdf', [
            'order' => $order->getId(),
            'orderDetails' => $firstOrderDetail->getId()
        ]);
        
        // Rediriger vers l'URL générée
        return $this->redirect($pdfUrl);
    }
    

    public function updatePreparation(AdminContext $context,EntityManagerInterface $entityManager)
    {
            $order = $context->getEntity()->getInstance();
            $order->setState(2);
            $entityManager->flush();

            $this->addFlash('notice', "<span style='color:green;'><strong>La commande ".$order->getReference()." est bien <u>en cours de préparation</u>.</strong></span>");

            $url = $this->adminUrlGenerator
            ->setController(OrderCrudController::class)
            ->setAction('index')
            ->generateUrl();
        return $this->redirect($url);
    }
    


    public function updateDelivery(AdminContext $context, EntityManagerInterface $entityManager)
    {
        $order = $context->getEntity()->getInstance();
        $order->setState(3);
        $entityManager->flush();

        $this->addFlash('notice', "<span style='color:orange;'><strong>La commande ".$order->getReference()." est bien <u>en cours de livraison</u>.</strong></span>");


        $url = $this->adminUrlGenerator
            ->setController(OrderCrudController::class)
            ->setAction('index')
            ->generateUrl();

        return $this->redirect($url);
    }


public function delivery(AdminContext $context, EntityManagerInterface $entityManager)
{
    $order = $context->getEntity()->getInstance();
    $order->setState(4);
    $entityManager->flush();

    $this->addFlash('notice', "<span style='color:orange;'><strong>La commande ".$order->getReference()." est bien <u>était livré</u>.</strong></span>");


    $url = $this->adminUrlGenerator
    ->setController(OrderCrudController::class)
    ->setAction('index')
    ->generateUrl();
    return $this->redirect($url);
    }

    public function configureCrud(Crud $crud): Crud
    {
       return $crud->setDefaultSort(['id'=>'DESC']);
    }
  
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')
                ->hideOnForm(),
            TextField::new('reference', 'Référance de la Commande')->hideOnIndex(),
            TextField::new('user.FullName', 'Client Nom')->hideOnIndex(),
            TextField::new('user.lastName', 'Client prénom')->hideOnIndex(),
            TextField::new('user.email', 'Client email')->hideOnIndex(),
            TextField::new('CarrierName', 'Nom de Livreur'),
            CollectionField::new('orderDetails', 'Détails de la commande')
                ->setTemplatePath('admin/partials/order_details_field.html.twig')
                ->hideOnIndex(),
            // AssociationField::new('product', 'Produit id'),
            IntegerField::new('quantity', 'quantité'),
            MoneyField::new('CarrierPrice','Expédition')->setCurrency('EUR'),
            MoneyField::new('subTotalHT','Sous TotalHT')->setCurrency('EUR'),
            MoneyField::new('Taxe','TVA')->setCurrency('EUR'),
            MoneyField::new('subTotalTTC','sousTotalTTC')->setCurrency('EUR'),
            BooleanField::new('isPaid','Commande payer'),
            ChoiceField::new('state')->setChoices([
                'Non payée' => 0,
                'Payée' => 1,
                'Préparation en cours' => 2,
                'Livraison en cours' => 3,
                'Livré' => 4
            ]),
           
        ];
    }
    
}
