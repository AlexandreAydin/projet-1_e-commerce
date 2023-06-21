<?php

namespace App\Controller\Stripe;

use App\Classe\OrderServices;
use App\Entity\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
Use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeCheckoutSessionController extends AbstractController
{
    #[Route('/commande/creation-session/{reference}', name: 'app_create_checkout_session')]
    public function index(
    ?Cart $cart,
    OrderServices $orderServices,
    EntityManagerInterface $manager): JsonResponse
    { 
        $stripeSecretKey = 'sk_test_51NKYgcB8m10ZC3XdC8i88ZoKozHjY1TFeTlxl2Tlue2EZ1fdnoe1j9naP5XXiXDcblZlP4xQSatmNtCyyaXgh6fd00DW3fvzF8';
        $YOUR_DOMAIN = 'http://127.0.0.1:8000';
        Stripe::setApiKey($stripeSecretKey);

        $user = $this->getUser();
        if(!$cart){
            return $this->redirectToRoute('app_home');
        }

        $order=$orderServices->createOrder($cart);

        $checkout_session = Session::create([
            "customer_email"=> $user->getEmail(),
            "payment_method_types" => ["card"],
            "line_items"=> $orderServices->getLineItems($cart),
            'mode'=> 'payment',
            'success_url'=>  $YOUR_DOMAIN .'/stripe-payment-succes/{CHECKOUT_SESSION_ID}',
            'cancel_url'=> $YOUR_DOMAIN .'/stripe-payment-annule/{CHECKOUT_SESSION_ID}',
        ]);

        $order->setStripeCheckoutSessionId($checkout_session->id);
        $manager->flush();


        return $this->json(['id'=>$checkout_session->id]);
    }


}
