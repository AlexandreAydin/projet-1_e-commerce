<?php

namespace App\Controller\Stripe;

use App\Entity\Order;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeCancelPayementController extends AbstractController
{
    #[Route('/stripe-payment-annule/{stripeCheckoutSessionId}', name: 'app_stripe_payment_cancel')]
    public function index(?Order $order): Response
    {

        if(!$order || $order->getUser() !== $this->getUser()){
            return $this->redirectToRoute("app_home");
        }

        return $this->render('stripe/stripe_cancel_payement/index.html.twig', [
            'controller_name' => 'StripeCancelPayementController',
            'order'=> $order
        ]);
    }
}
