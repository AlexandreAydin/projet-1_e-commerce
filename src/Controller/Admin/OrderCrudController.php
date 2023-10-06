<?php

namespace App\Controller\Admin;

use App\Classe\Mail;
use App\Entity\Order;
use App\Repository\ProductRepository;
use App\Service\PdfService;
use DateTime;
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
use Symfony\Component\Routing\RouterInterface;
use Doctrine\ORM\Mapping as ORM;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class OrderCrudController extends AbstractCrudController
{

    private $entityManager;
    private $adminUrlGenerator;
    private $pdfService;
    private $mail;
    private $router;

    public function __construct(
    EntityManagerInterface $entityManager, 
    AdminUrlGenerator $adminUrlGenerator,
    RouterInterface $router,
    PdfService $pdfService,
    Mail $mail)
    {
        $this->entityManager = $entityManager;
        $this->adminUrlGenerator = $adminUrlGenerator;
        $this->pdfService = $pdfService;
        $this->mail = $mail;
        $this->router = $router;
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


    public function delivery(AdminContext $context,ProductRepository $productRepository, EntityManagerInterface $entityManager)
    {
        $order = $context->getEntity()->getInstance();
        $order->setState(4);
        $entityManager->flush();
    
        // Logique d'envoi de mail
        $product = $productRepository->findOneBy(['name' => $order->getProductName()]);
        $this->sendDeliveryMail($order, $product, $this->router, $this->mail);
        // $content = "Bonjour " . $order->getUser()->getFirstname() . ",<br/><br/>";
        // $content .= "Nous sommes ravis de vous informer que votre colis a été livré.<br/><br/>";
        // $this->mail->send($order->getUser()->getEmail(), $order->getUser()->getFirstname(), 'Votre commande Anamoz est bien validée.', $content);
    
        $this->addFlash('notice', "<span style='color:orange;'><strong>La commande ".$order->getReference()." est bien <u>était livré</u>.</strong></span>");
    
        $url = $this->adminUrlGenerator
        ->setController(OrderCrudController::class)
        ->setAction('index')
        ->generateUrl();
        return $this->redirect($url);
    }

    private function sendDeliveryMail(Order $order, $product, $router, $mail) {
        $url = $router->generate('app_single_product', ['slug' => $product->getSlug()]);
        $content = "Bonjour " . $order->getUser()->getFirstname() . ",<br/><br/>";
        $content .= "Nous sommes ravis de vous informer que votre colis a été livré.<br/><br/>";
        $content .= "Nous espérons que vous êtes satisfait de votre achat. Si vous avez des questions ou des préoccupations concernant votre commande, n'hésitez pas à nous contacter.<br/><br/>";
        $content .= "Nous apprécions énormément votre confiance en choisissant de magasiner chez nous. Votre avis compte beaucoup pour nous. Si vous le souhaitez, vous pouvez laisser un commentaire sur le produit que vous avez acheté en cliquant sur le lien suivant : <a href='" . $url . "'>donner votre avis</a>.<br/><br/>";
        $content .= "Merci encore pour votre achat. Nous espérons vous revoir bientôt !<br/><br/>";
        $content .= "Cordialement,<br/>";
        $content .= "L'équipe Anamoz";
    
        $mail->send($order->getUser()->getEmail(), $order->getUser()->getFirstname(), 'Votre colis Anamoz a été livré', $content);
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
            DateTimeField::new('createdAt','Date de la commande'),
           
        ];
    }
    
}
