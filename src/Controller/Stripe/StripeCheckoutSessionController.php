<?php

namespace App\Controller\Stripe;

use App\Classe\OrderServices;
use App\Entity\Cart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
Use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeCheckoutSessionController extends AbstractController
{
    #[Route('/commande/creation-session/{reference}', name: 'app_create_checkout_session')]
    public function index(?Cart $cart, OrderServices $orderServices): JsonResponse
    { 
        $stripeSecretKey = 'sk_test_51NKYgcB8m10ZC3XdC8i88ZoKozHjY1TFeTlxl2Tlue2EZ1fdnoe1j9naP5XXiXDcblZlP4xQSatmNtCyyaXgh6fd00DW3fvzF8';
        $YOUR_DOMAIN = 'http://127.0.0.1:8000';
        $orderServices->createOrder($cart);
        Stripe::setApiKey($stripeSecretKey);

        $user = $this->getUser();
        if(!$cart){
            return $this->redirectToRoute('app_home');
        }

        $checkout_session = Session::create([
            "customer_email"=> $user->getEmail(),
            "payment_method_types" => ["card"],
            "line_items"=> $orderServices->getLineItems($cart),
            'mode'=> 'payment',
            'success_url'=>  $YOUR_DOMAIN .'/stripe-payment-succes',
            'cancel_url'=> $YOUR_DOMAIN .'/stripe-payment-annule',
        ]);


        return $this->json(['id'=>$checkout_session->id]);
    }


}
