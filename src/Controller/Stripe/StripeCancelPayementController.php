<?php

namespace App\Controller\Stripe;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StripeCancelPayementController extends AbstractController
{
    #[Route('/stripe-payment-annule', name: 'app_stripe_payment_cancel')]
    public function index(): Response
    {
        return $this->render('stripe/stripe_cancel_payement/index.html.twig', [
            'controller_name' => 'StripeCancelPayementController',
        ]);
    }
}
