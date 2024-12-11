<?php

namespace App\Controller\Paypal;

use App\Classe\Mail;
use App\Classe\OrderServices;
use App\Classe\StockManagerServices;
use App\Entity\Cart;
use App\Entity\Coupon;
use App\Repository\OrderRepository;
use App\Services\PaypalService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Annotation\Route;

class PaypalCheckoutSessionController extends AbstractController
{
    private $paypalService;
    private $client;
    private $paypal_public_key;
    private $paypal_private_key;
    private $base;

    public function __construct(PaypalService $paypalService, HttpClientInterface $client)
    {
        $this->paypalService = $paypalService;
        $this->paypal_public_key = $this->paypalService->getPublicKey();
        $this->paypal_private_key = $this->paypalService->getPrivateKey();
        $this->base = $this->paypalService->getBaseUrl();
        $this->client = $client;
    }

    #[Route('/commande/creation-session/paypal/{reference}', name: 'app_paypal_create_checkout_session')]
    public function createCheckoutSession(
        ?Cart $cart,
        OrderServices $orderServices,
        EntityManagerInterface $manager,
        RequestStack $requestStack
    ): JsonResponse 
    {
        if (!$cart) {
            return $this->json(['error' => 'Le panier est introuvable.'], 400);
        }
    
        // Récupération des informations du panier
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
    
        // Création de la commande
        $order = $orderServices->createOrder($cart);
        if (!$order) {
            return $this->json(['error' => 'La commande n\'a pas pu être créée.'], 500);
        }
    
        // Associe le coupon à la commande, si applicable
        if ($coupon) {
            $order->setCoupon($coupon);
        }
    
        // Appel de l'API PayPal pour créer une commande
        $amountValue = number_format($finalTTC / 100, 2, '.', ''); // Conversion pour PayPal
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => $amountValue,
                    ],
                ],
            ],
        ];
    
        try {
            $accessToken = $this->generateAccessToken();
            $url = $this->base . '/v2/checkout/orders';
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
                'json' => $payload,
            ]);
            $result = $this->handleResponse($response);
    
            if (isset($result['jsonResponse']['id'])) {
                $id = $result['jsonResponse']['id'];
                $order->setPaypalClientSecret($id);
                $manager->persist($order);
                $manager->flush();
    
                return $this->json($result['jsonResponse']);
            }
    
            return $this->json(['error' => 'Impossible de créer la session PayPal.'], 500);
        } catch (\Exception $e) {
            error_log('Erreur lors de la création de la session PayPal : ' . $e->getMessage());
            return $this->json(['error' => 'Erreur lors de la création de la session PayPal.'], 500);
        }
    }
    

    #[Route('/api/orders/capture{reference}', name: 'app_capture_paypal', methods:['POST'])]
    public function capturePayment(
        $reference,
        Request $req,
        OrderRepository $orderRepo,
        StockManagerServices $stockManager,
        EntityManagerInterface $em,
        \App\Classe\Mail $mailService  // Assurez-vous d'injecter votre service de Mail ici
    ): JsonResponse
    {
        try {
            $order = $orderRepo->findOneByReference($reference);
    
            if (!$order) {
                return $this->json(['error' => "Order not found!"], 404);
            }
    
            $paypalClientSecret = $order->getPaypalClientSecret();
            $result = $this->captureOrder($paypalClientSecret);
    
            if (isset($result['jsonResponse']['id']) && isset($result['jsonResponse']['status'])) {
                $id = $result['jsonResponse']['id'];
                $status = $result['jsonResponse']['status'];
    
                if ($status === "COMPLETED") {
                    $order->setIsPaid(true);
                    $order->setPaymentMethod("PAYPAL");
                    $stockManager->deStock($order);
    
                    $em->persist($order);
                    $em->flush();
    
                    // Préparation du contenu du mail
                    $content = "Bonjour " . $order->getUser()->getFirstname() . 
                               "<br/> <br/> Merci pour votre commande." .
                               "<br/><br/>Numéro de Commande: " . $order->getId() .
                               "<br/><br/>Référence de Commande: " . $order->getReference() .
                               "<br><br/>Vous recevrez bientôt votre colis.<br/> Vous pouvez suivre le statut de votre commande dans votre espace personnel.";
    
                    // Envoi du mail
                    $mailService->send($order->getUser()->getEmail(), $order->getUser()->getFirstname(), 'Votre commande Anamoz est bien validée.', $content);
                }
            }
    
            return $this->json($result['jsonResponse']);
    
        } catch (Exception $error) {
            error_log("Failed to capture order: " . $error->getMessage());
            return $this->json(["error" => "Failed to capture order."], 500);
        }
    }
    

    public function generateAccessToken()
    {
        $auth = base64_encode($this->paypal_public_key . ":" . $this->paypal_private_key);

        $response = $this->client->request(
            'POST',
            $this->base.'/v1/oauth2/token',
            [
                'body' => "grant_type=client_credentials",
                'headers' => ['Authorization'=> "Basic ". $auth]
            ]
        );

        $data = $response->toArray();

        return $data['access_token'];
    }

    public function createOrder($order)
    {
        $accessToken = $this->generateAccessToken();
        $url = $this->base . '/v2/checkout/orders';

        $amountValue = number_format($order->getSubTotalTTC() / 100, 2, '.', '');

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => 'EUR',
                        'value' => $amountValue,
                    ],
                ],
            ],
        ];

        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ],
            'json' => $payload,
        ]);

        return $this->handleResponse($response);
    }

    public function captureOrder($orderID)
    {
        $accessToken = $this->generateAccessToken();
        $url = $this->base . '/v2/checkout/orders/' . $orderID . '/capture';

        $response = $this->client->request('POST', $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ]);

        return $this->handleResponse($response);
    }

    public function handleResponse($response)
    {
        try {
            $jsonResponse = json_decode($response->getContent(), true);
            return [
                'jsonResponse' => $jsonResponse,
                'httpStatusCode' => $response->getStatusCode(),
            ];
        } catch (\Exception $error) {
            $errorMessage = $response->getContent(false);
            throw new \Exception($errorMessage);
        }
    }
}
