<?php

namespace App\Controller\Cart;

use App\Repository\CouponRepository;
use App\Repository\ProductVariantRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    private $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    #[Route('/panier', name: 'app_cart')]
    public function index(): JsonResponse
    {
        $cart = $this->cartService->getFullCart();

        dump($cart);exit();
        // Suppression des données utilisateurs ou d'autres objets non nécessaires
        foreach ($cart['products'] as &$product) {
            unset($product['variant']['user']);  // Exemple : suppression de la propriété user
        }
        
        return $this->json($cart);
    }

    #[Route('/panier/{variantId}/ajouter/{quantity}', name: 'app_add_to_cart')]
    public function addToCart(
        int $variantId,
        int $quantity,
        Request $request,
        ProductVariantRepository $repoProductVariant,
        CartService $cartService
    ): JsonResponse {
        $size = $request->query->get('size');
        $color = $request->query->get('color');
    
        error_log("Ajout au panier : Variant ID = {$variantId}, Taille = {$size}, Couleur = {$color}");
    
        $variant = $repoProductVariant->find($variantId);
        if (!$variant) {
            return $this->json(['error' => 'Variante non trouvée'], 404);
        }
    
        error_log("Prix de la variante : {$variant->getPrice()}");
    
        $cartService->addToCart($variantId, $quantity, $size, $color);
        return $this->json($cartService->getFullCart());
    }
    
    


    
    
    
    
    #[Route('/mon-panier/{variantId}/ajouter', name: 'app_cart_increase')]
    public function increaseQuantity(Request $request, int $variantId, CartService $cartService): JsonResponse
    {
        $size = $request->query->get('size', 'DefaultSize');
        $color = $request->query->get('color', 'DefaultColor');
    
        error_log("Données reçues : Variant ID = {$variantId}, Taille = {$size}, Couleur = {$color}");
    
        try {
            $cartService->addToCart($variantId, 1, $size, $color);
            $cart = $cartService->getFullCart();
            error_log("Cart après ajout : " . json_encode($cart));
            return $this->json($cart);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    
    
    
    




    #[Route('/mon-panier/{variantId}/diminuer', name: 'app_cart_decrease', methods: ['POST'])]
    public function decreaseQuantity(
        int $variantId,
        Request $request,
        CartService $cartService
    ): JsonResponse {
        $size = $request->query->get('size', 'DefaultSize');
        $color = $request->query->get('color', 'DefaultColor');
    
        try {
            $cartService->decreaseQuantity($variantId, $size, $color);
            return $this->json($cartService->getFullCart());
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
    
    

    #[Route('/mon-panier/{variantId}/supprimer', name: 'app_cart_delete')]
    public function removeFromCart($variantId,Request $request): JsonResponse
    {
        $size = $request->query->get('size', 'DefaultSize');
        $color = $request->query->get('color', 'DefaultColor');

        try {
            $this->cartService->deleteAllFromCart($variantId, $size, $color);
            return $this->json([
                'message' => 'Produit supprimé du panier',
                'cart' => $this->cartService->getFullCart(),
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la suppression du produit: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/mon-panier/{variantId}/tout-supprimer', name: 'app_cart_clear')]
    public function clearCart($variantId, CartService $cartService,Request $request): JsonResponse
    {

        $size = $request->query->get('size', 'DefaultSize');
        $color = $request->query->get('color', 'DefaultColor');

        try {
            $cartService->deleteAllFromCart($variantId ,$size, $color);
    
            // Retourner le panier mis à jour
            return $this->json($cartService->getFullCart());
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/mon-panier/obtenir', name: 'app_get_cart')]
    public function getCart(): JsonResponse
    {
        $cart = $this->cartService->getFullCart();
        return $this->json($cart);
    }


    #[Route('/cart/apply-coupon', name: 'apply_coupon', methods: ['POST'])]
    public function applyCoupon(
        Request $request,
        SessionInterface $session,
        CouponRepository $couponRepository,
        CartService $cartService,
        EntityManagerInterface $manager
    ): JsonResponse {
        // Log de la requête reçue
        $data = json_decode($request->getContent(), true);
        error_log('Requête reçue : ' . json_encode($data));
    
        // Validation du code promo
        $couponCode = $data['coupon_code'] ?? null;
        if (!$couponCode) {
            error_log('Erreur : Aucun code coupon fourni.');
            return new JsonResponse(['success' => false, 'message' => 'Aucun code coupon fourni.'], 400);
        }
    
        // Recherche du coupon
        $coupon = $couponRepository->findOneBy(['code' => $couponCode]);
        if (!$coupon) {
            error_log('Erreur : Le code coupon est introuvable.');
            return new JsonResponse(['success' => false, 'message' => 'Le code coupon est introuvable.'], 400);
        }
    
        // Vérification de l'état actif du coupon
        if (!$coupon->isActive()) {
            error_log('Erreur : Le code coupon est inactif.');
            return new JsonResponse(['success' => false, 'message' => 'Le code coupon est inactif.'], 400);
        }
    
        // Vérification de la date d'expiration
        if ($coupon->getExpirationDate() && $coupon->getExpirationDate() < new \DateTime()) {
            error_log('Erreur : Le code coupon est expiré.');
            return new JsonResponse(['success' => false, 'message' => 'Le code coupon est expiré.'], 400);
        }
    
        // Récupération du panier
        $cart = $cartService->getCartEntity();
        error_log('Panier récupéré : ' . json_encode($cart)); // Log pour vérifier le panier
    
        // Vérification du panier
        if (!$cart) {
            error_log('Erreur : Le panier est introuvable.');
            return new JsonResponse(['success' => false, 'message' => 'Le panier est introuvable.'], 400);
        }
    
        // Vérification de l'utilisateur
        $user = $this->getUser();
        if ($cart->getUser()->getId() !== $user->getId()) {
            error_log('Erreur : L\'utilisateur actuel ne correspond pas au propriétaire du panier.');
            return new JsonResponse(['success' => false, 'message' => 'Vous ne pouvez pas appliquer un coupon à ce panier.'], 403);
        }
    
        // Application du coupon
        $cart->setCouponApplied(true);
        $manager->persist($cart);
        $manager->flush();
    
        // Enregistrer le coupon dans la session
        $discountPercentage = $coupon->getDiscountAmount();
        $session->set('applied_coupon', [
            'code' => $coupon->getCode(),
            'discountPercentage' => $discountPercentage,
        ]);
    
        // Récupérer les données du panier mises à jour
        $cartData = $cartService->getFullCart();
    
        return new JsonResponse([
            'success' => true,
            'message' => 'Le coupon a été appliqué avec succès.',
            'discountPercentage' => $discountPercentage,
            'cart' => $cartData,
        ]);
    }
    
    

    
    
    
    


}
