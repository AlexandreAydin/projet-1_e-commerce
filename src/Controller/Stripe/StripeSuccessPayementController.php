<?php

namespace App\Controller\Stripe;

use App\Entity\Order;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeSuccessPayementController extends AbstractController
{
    #[Route('/stripe-payment-succes/{StripeCheckoutSessionId}', name: 'app_stripe_payment_success')]
    public function index(?Order $order,
    CartService $cartServices,
    EntityManagerInterface $manager): Response
    {
        if(!$order || $order->getUser() !== $this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        if(!$order->getIsPaid()){
            // Commande payer
            $order->setIsPaid(true);
            $manager->flush();
            $cartServices->deleteCart();
            // un mail au client
        }
        
        return $this->render('stripe/stripe_success_payement/index.html.twig', [
            'controller_name' => 'StripeSuccessPayementController',
            'order'=> $order
        ]);
    }
}
