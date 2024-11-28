<?php

namespace App\Controller\Cart;

use App\Repository\ProductVariantRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    
        try {
            $cartService->addToCart($variantId, 1, $size, $color);
            return $this->json($cartService->getFullCart());
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

}
