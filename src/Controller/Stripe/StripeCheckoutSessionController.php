<?php

namespace App\Controller\Stripe;

use App\Classe\OrderServices;
use App\Entity\Cart;
use App\Entity\Coupon;
use App\Services\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\HttpFoundation\RequestStack;

class StripeCheckoutSessionController extends AbstractController
{
    #[Route('/commande/creation-session/{reference}', name: 'app_create_checkout_session', methods: ['POST'])]
    public function index(
        ?Cart $cart,
        StripeService $stripeService,
        OrderServices $orderServices,
        EntityManagerInterface $manager,
        RequestStack $requestStack
    ): JsonResponse {
        $stripeSecretKey = $stripeService->getPrivateKey();
        Stripe::setApiKey($stripeSecretKey);
    
        $user = $this->getUser();
    
        if (!$cart) {
            return $this->json(['error' => 'Le panier est introuvable.'], 400);
        }
    
        // Récupérer les informations du panier
        $subTotalTTC = $cart->getSubTotalTTC();
        $carrierPrice = $cart->getCarrierPrice();
    
        // Vérification du code promo
        $session = $requestStack->getSession();
        $appliedCouponData = $session->get('applied_coupon', null);
    
        $discountPercentage = 0; // Pourcentage de réduction
        $coupon = null;
    
        if ($appliedCouponData) {
            $coupon = $manager->getRepository(Coupon::class)->findOneBy(['code' => $appliedCouponData['code']]);
    
            if ($coupon instanceof Coupon && $coupon->isActive() && (!$coupon->getExpirationDate() || $coupon->getExpirationDate() >= new \DateTime())) {
                $discountPercentage = (float) $coupon->getDiscountAmount(); // Récupère le pourcentage
            }
        }
    
        // Calculer le montant final après application du pourcentage
        $discountAmount = ($subTotalTTC * $discountPercentage) / 100;
        $finalTTC = $subTotalTTC - $discountAmount + $carrierPrice;
    
        // Conversion en centimes pour Stripe
        $amountInCents = intval(round($finalTTC * 100)); // Convertir en centimes
        if ($amountInCents > 0) {
            $amountInCents = $amountInCents / 100; // Corriger pour éviter les erreurs (facultatif)
        }
    
        // Création de la session Stripe Checkout
        $checkout_session = Session::create([
            "payment_method_types" => ["card"],
            "customer_email"=> $user->getEmail(),
            "line_items" => [[
                "price_data" => [
                    "currency" => "eur",
                    "product_data" => [
                        "name" => "Votre commande",
                        "description" => $discountPercentage > 0
                            ? "Code promo appliqué : réduction de " . number_format($discountPercentage, 2) . " %"
                            : "Sans réduction",
                    ],
                    "unit_amount" => $amountInCents,
                ],
                "quantity" => 1,
            ]],
            "mode" => "payment",
            "success_url" => 'http://127.0.0.1:8000/stripe-payment-succes/{CHECKOUT_SESSION_ID}',
            "cancel_url" => 'http://127.0.0.1:8000/stripe-payment-annule/{CHECKOUT_SESSION_ID}',
        ]);
    
        // Création et sauvegarde de la commande
        $order = $orderServices->createOrder($cart);
        $order->setStripeCheckoutSessionId($checkout_session->id);
    
        if ($coupon) {
            $order->setCoupon($coupon); // Associe le coupon à la commande
        }
    
        $manager->flush();
    
        return $this->json(['id' => $checkout_session->id]);
    }

}
