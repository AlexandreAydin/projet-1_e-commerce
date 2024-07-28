<?php

namespace App\Controller\Paypal;

use App\Classe\Mail;
use App\Classe\OrderServices;
use App\Entity\Cart;
use App\Repository\OrderRepository;
use App\Services\PaypalService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
        EntityManagerInterface $manager
    ): JsonResponse 
    {
        if (!$cart) {
            return $this->redirectToRoute('app_home');
        }
        
        // Create the order using the order services
        $order = $orderServices->createOrder($cart);

        if (!$order) {
            return $this->json(['error' => "Order not found!"], 404);
        }

        // Call PayPal API to create an order
        $result = $this->createOrder($order);

        if (isset($result['jsonResponse']['id'])) {
            $id = $result['jsonResponse']['id'];
            $order->setPaypalClientSecret($id);
            $manager->persist($order);
            $manager->flush();
        }

        return $this->json($result['jsonResponse']);
    }

    #[Route('/api/orders/capture{reference}', name: 'app_capture_paypal', methods:['POST'])]
    public function capturePayment(
        $reference,
        Request $req,
        OrderRepository $orderRepo,
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
