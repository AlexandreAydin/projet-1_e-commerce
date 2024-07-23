<?php

namespace App\Controller\Stripe;

use App\Classe\OrderServices;
use App\Entity\Cart;
use App\Services\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeCheckoutSessionController extends AbstractController
{
    #[Route('/commande/creation-session/{reference}', name: 'app_create_checkout_session')]
    public function index(
        ?Cart $cart,
        StripeService $stripeService,
        OrderServices $orderServices,
        EntityManagerInterface $manager
    ): JsonResponse {
        $stripeSecretKey = $stripeService->getPrivateKey();
        $YOUR_DOMAIN = 'http://127.0.0.1:8000';
        Stripe::setApiKey($stripeSecretKey);

        $user = $this->getUser();

        if (!$cart) {
            return $this->redirectToRoute('app_home');
        }

        $order = $orderServices->createOrder($cart);

        // Assurez-vous de définir $amountInCents correctement, c'est un exemple
       // Supposons que ces méthodes vous retournent des valeurs float ou int
    $subTotalTTC = $cart->getSubTotalTTC();  // par exemple, 105.98
    $carrierPrice = $cart->getCarrierPrice(); // par exemple, 0 (si vous n'avez pas de frais de port)

    // Conversion en centimes
    $amountInCents = intval(round(($subTotalTTC + $carrierPrice)));

    $checkout_session = Session::create([
        "payment_method_types" => ["card"],
        "line_items"=> $orderServices->getLineItems($cart),
        'mode'=> 'payment',
        'success_url'=>  $YOUR_DOMAIN .'/stripe-payment-succes/{CHECKOUT_SESSION_ID}',
        'cancel_url'=> $YOUR_DOMAIN .'/stripe-payment-annule/{CHECKOUT_SESSION_ID}',
        "customer_email"=> $user->getEmail(),
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => 'Nom réel du produit',
                ],
                'unit_amount' => $amountInCents,
            ],
            'quantity' => 1,
        ]],
    ]);

        $order->setStripeCheckoutSessionId($checkout_session->id);
        $manager->flush();

        return $this->json(['id' => $checkout_session->id]);
    }
}
