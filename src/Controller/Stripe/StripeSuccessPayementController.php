<?php

namespace App\Controller\Stripe;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeSuccessPayementController extends AbstractController
{
    #[Route('/stripe-payment-succes', name: 'app_stripe_payment_success')]
    public function index(): Response
    {
        return $this->render('stripe/stripe_success_payement/index.html.twig', [
            'controller_name' => 'StripeSuccessPayementController',
        ]);
    }
}
