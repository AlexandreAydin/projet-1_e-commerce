<?php

namespace App\Controller\Stripe;

use App\Classe\StockManagerServices as ClasseStockManagerServices;
use App\Entity\Order;
use App\Service\CartService;
use App\Classe\StockManagerServices;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeSuccessPayementController extends AbstractController
{
    #[Route('/stripe-payment-succes/{StripeCheckoutSessionId}', name: 'app_stripe_payment_success')]
    public function index(?Order $order,
    CartService $cartServices,
    ClasseStockManagerServices $stockManager,
    EntityManagerInterface $manager): Response
    {
        if(!$order || $order->getUser() !== $this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        if(!$order->getIsPaid()){
            // Commande payer
            $order->setIsPaid(true);
            //déstockage
            $stockManager->deStock($order);
            $manager->flush();
            $cartServices->deleteCart();
        }

        if($order->getState()==0){
            // Commande payer
            $order->setState(1);
            //déstockage
            $manager->flush();
            $cartServices->deleteCart();
        }
        
        return $this->render('stripe/stripe_success_payement/index.html.twig', [
            'controller_name' => 'StripeSuccessPayementController',
            'order'=> $order
        ]);
    }
}
