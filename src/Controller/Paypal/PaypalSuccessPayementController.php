<?php

namespace App\Controller\Paypal;

use App\Classe\Mail;
use App\Classe\StockManagerServices as ClasseStockManagerServices;
use App\Entity\Order;
use App\Service\CartService;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaypalSuccessPayementController extends AbstractController
{
    #[Route('/paypal-payment-succes/{reference}', name: 'app_paypal_payment_success')]
    public function index(
        ?Order $order,
        CartService $cartServices,
        ClasseStockManagerServices $stockManager,
        EntityManagerInterface $manager
    ): Response {


        if (!$order || $order->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
    

        $order->setIsPaid(true);
        
        
        // Commande payée
        if (!$order->getIsPaid()) {
            $order->setIsPaid(true);
            $stockManager->deStock($order);
            $manager->flush();
            $cartServices->deleteCart();
        }
    
        // Si l'état est 0, passez-le à 1
        if ($order->getState() == 0) {
            $order->setState(1);
            $manager->flush();
            $cartServices->deleteCart();
        }

    
        return $this->render('paypal/paypal_success_payement/index.html.twig', [
            'controller_name' => 'StripeSuccessPayementController',
            'order' => $order
        ]);
    }
    
    
}
