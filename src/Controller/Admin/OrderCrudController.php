<?php

namespace App\Controller\Admin;

use App\Classe\Mail;
use App\Entity\Order;
use App\Entity\Product;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;

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
        $updateDelivery = Action::new('updateDelivery', 'Livraison en cours', 'fas fa-truck')
        ->linkToCrudAction('updateDeliveryForm');
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

    // Ajoutez cette nouvelle méthode
    private function sendShippingNotification(Order $order, $product, $router, $mail)
    {
        $trackingUrl = "https://suivi.transporteur.com/?tracking=" . $order->getTrackingNumber();
        
        $content = "Bonjour " . $order->getUser()->getFirstname() . ",<br/><br/>";
        $content .= "Votre commande n°" . $order->getReference() . " est en cours de livraison !<br/><br/>";
        $content .= "<strong>Numéro de suivi :</strong> " . $order->getTrackingNumber() . "<br/>";
        $content .= "Suivez votre colis en temps réel : <a href='" . $trackingUrl . "'>Cliquez ici</a><br/><br/>";
        $content .= "Détails de livraison :<br/>";
        $content .= "- Transporteur : " . $order->getCarrierName() . "<br/>";
        $content .= "- Adresse : " . $order->getDeliveryAddress() . "<br/><br/>";
        $content .= "Nous restons à votre disposition pour toute question.<br/><br/>";
        $content .= "Cordialement,<br/>";
        $content .= "L'équipe Yilmi Market";

        $mail->send(
            $order->getUser()->getEmail(),
            $order->getUser()->getFirstname(),
            'Votre commande Yilmi Market est en route !',
            $content
        );
    }
    


    public function updateDeliveryForm(AdminContext $context, Request $request, EntityManagerInterface $entityManager)
    {
        $order = $context->getEntity()->getInstance();
        $productRepository = $this->entityManager->getRepository(Product::class);
        $product = $productRepository->findOneBy(['name' => $order->getProductName()]);
    
        $form = $this->createFormBuilder()
            ->add('trackingNumber', TextType::class, [
                'label' => 'Numéro de suivi',
                'required' => true
            ])
            ->getForm();
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $order->setTrackingNumber($data['trackingNumber']);
            $order->setState(3);
            $entityManager->flush();
    
            // Appel de la nouvelle méthode d'envoi
            $this->sendShippingNotification($order, $product, $this->router, $this->mail);
    
            $this->addFlash('success', 'Le numéro de suivi a été enregistré et le client a été notifié.');
            return $this->redirect($this->adminUrlGenerator->setController(OrderCrudController::class)->setAction('index')->generateUrl());
        }
    
        return $this->render('admin/order/tracking_form.html.twig', [
            'form' => $form->createView(),
            'order' => $order,
        ]);
    }


    


    public function delivery(AdminContext $context, ProductRepository $productRepository, EntityManagerInterface $entityManager)
    {
        $order = $context->getEntity()->getInstance();
        $order->setState(4);
        $entityManager->flush();
    
        $product = $productRepository->findOneBy(['name' => $order->getProductName()]);
        
        // Appel de la méthode renommée
        $this->sendDeliveryConfirmation($order, $product, $this->router, $this->mail);
    
        $this->addFlash('notice', "<span style='color:green;'><strong>La commande ".$order->getReference()." est marquée comme livrée.</strong></span>");
    
        return $this->redirect(
            $this->adminUrlGenerator
                ->setController(OrderCrudController::class)
                ->setAction('index')
                ->generateUrl()
        );
    }
    

    // Renommez et ajustez cette méthode
private function sendDeliveryConfirmation(Order $order, $product, $router, $mail) 
{
    $url = $router->generate('app_single_product', ['slug' => $product->getSlug()]);
    
    $content = "Bonjour " . $order->getUser()->getFirstname() . ",<br/><br/>";
    $content .= "Nous confirmons que votre commande n°" . $order->getReference() . " a bien été livrée !<br/><br/>";
    $content .= "<strong>Date de livraison :</strong> " . (new DateTime())->format('d/m/Y') . "<br/><br/>";
    $content .= "Merci d'avoir choisi Yilmi Market. Nous espérons que vous êtes satisfait de votre achat.<br/><br/>";
    $content .= "Votre avis compte beaucoup pour nous <br/>";
    $content .= "Laissez un commentaire sur le produit : <a href='" . $url . "'>Je donne mon avis</a><br/><br/>";
    $content .= "À très bientôt,<br/>";
    $content .= "L'équipe Yilmi Market";

    $mail->send(
        $order->getUser()->getEmail(),
        $order->getUser()->getFirstname(),
        'Votre commande Yilmi Market a été livrée avec succès !',
        $content
    );
}
    

    public function configureCrud(Crud $crud): Crud
    {
       return $crud->setDefaultSort(['id'=>'DESC']);
    }
  
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('reference', 'Référance de la Commande')->hideOnIndex(),
            TextField::new('user.FullName', 'Client Nom')->hideOnIndex(),
            TextField::new('user.lastName', 'Client prénom')->hideOnIndex(),
            TextField::new('user.email', 'Client email')->hideOnIndex(),
            TextField::new('CarrierName', 'Nom de Livreur')->hideOnIndex(),
            TextField::new('paymentMethod'),
            TextField::new('stripeClientSecret')->hideOnIndex(),
            TextField::new('paypalClientSecret')->hideOnIndex(),
            TextField::new('deliveryAddress', "Addresse de la livraison")->hideOnIndex(),
            TextField::new('billingAddress', "Addresse de facturation")->hideOnIndex(),
            CollectionField::new('orderDetails', 'Détails de la commande')
                ->setTemplatePath('admin/partials/order_details_with_variant.html.twig')
                ->hideOnIndex(),
            IntegerField::new('quantity', 'quantité'),
            MoneyField::new('CarrierPrice','Expédition')->setCurrency('EUR'),
            MoneyField::new('subTotalHT','Sous TotalHT')->setCurrency('EUR'),
            MoneyField::new('Taxe','TVA')->setCurrency('EUR'),
            MoneyField::new('subTotalTTC','sousTotalTTC')->setCurrency('EUR'),
            TextField::new('trackingNumber', 'Numéro de suivi')
                ->hideOnIndex()
                ->setPermission('ROLE_ADMIN'),
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
