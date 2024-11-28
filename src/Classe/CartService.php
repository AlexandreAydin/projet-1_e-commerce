<?php

namespace App\Service;

use App\Repository\ProductRepository;
use App\Repository\ProductVariantRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private $requestStack;
    private $repoProduct;
    private $repoProductVariant;
    private $tva = 0.2;

    public function __construct(RequestStack $requestStack,
    ProductRepository $repoProduct,
    ProductVariantRepository $repoProductVariant)
    {
        $this->requestStack = $requestStack;
        $this->repoProduct = $repoProduct;
        $this->repoProductVariant = $repoProductVariant;
        
    }

    private function getSession()
    {
        return $this->requestStack->getSession();
    }

    public function saveCart(array $cart): void
    {
        $this->getSession()->set('cart', $cart);
    }
    
    public function addToCart(int $variantId, int $quantity, string $size, string $color): void
    {
        $cart = $this->getCart();
        $variant = $this->repoProductVariant->find($variantId);
    
        if (!$variant) {
            throw new \Exception('Variante non trouvée.');
        }
    
        // Logs pour déboguer
        error_log("Ajout au panier : Variant ID = {$variantId}, Taille = {$size}, Couleur = {$color}");
        error_log("Prix de la variante : {$variant->getPrice()}, Remise : {$variant->getOffVariant()}%");
    
        foreach ($cart as &$item) {
            if (
                $item['variantId'] === $variantId &&
                $item['selectedSize'] === $size &&
                $item['selectedColor'] === $color
            ) {
                $item['quantity'] += $quantity;
                $this->saveCart($cart);
                return;
            }
        }
    
        $cart[] = [
            'variantId' => $variantId,
            'quantity' => $quantity,
            'selectedSize' => $size,
            'selectedColor' => $color,
            'price' => $variant->getPrice(), // Prix TTC
            'offVariant' => $variant->getOffVariant(),
        ];
    
        $this->saveCart($cart);
    }
    
    
    
    

    public function decreaseQuantity(int $variantId, string $size, string $color): void
    {
        $cart = $this->getCart();

        foreach ($cart as $key => &$item) {
            if (
                $item['variantId'] === $variantId &&
                $item['selectedSize'] === $size &&
                $item['selectedColor'] === $color
            ) {
                // Réduire la quantité
                if ($item['quantity'] > 1) {
                    $item['quantity']--;
                } else {
                    // Si la quantité est 1, supprimez le produit du panier
                    unset($cart[$key]);
                }

                // Sauvegarder le panier après modification
                $this->saveCart($cart);
                return;
            }
        }
    }

    public function cleanCart()
    {
        $cart = $this->getCart();

        foreach ($cart as $variantId => $item) {
            // Si l'élément est un scalaire, on le transforme en tableau avec une quantité par défaut
            if (!is_array($item)) {
                $cart[$variantId] = [
                    'quantity' => $item,
                    'selectedSize' => null
                ];
            }
        }

        $this->updateCart($cart);  // Mettre à jour le panier
    }


    /**
     * Supprimer complètement un produit du panier
     */
    public function deleteAllFromCart($variantId, string $size, string $color)
    {
        $cart = $this->getCart();

        // Rechercher et supprimer l'élément correspondant
        foreach ($cart as $key => $item) {
            if (
                $item['variantId'] == $variantId &&
                $item['selectedSize'] === $size &&
                $item['selectedColor'] === $color
                ) {
                unset($cart[$key]);
            }
        }

        // Mettre à jour le panier dans la session
        $this->updateCart($cart);
    }


    /**
     * Vider complètement le panier
     */
    public function deleteCart()
    {
        $this->updateCart([]); // Réinitialiser le panier
    }

    /**
     * Mettre à jour le panier dans la session
     */
    public function updateCart($cart)
    {
        $this->getSession()->set('cart', $cart);  // Mettre à jour le panier dans la session
    }
    /**
     * Récupérer le panier depuis la session
     */
    public function getCart(): array
    {
        $cart = $this->getSession()->get('cart', []);
        
        // Vérification et nettoyage du panier pour s'assurer que chaque élément est un tableau valide
        foreach ($cart as $key => $item) {
            if (!is_array($item) || !isset($item['variantId'], $item['quantity'])) {
                unset($cart[$key]); // Supprime les éléments invalides
            }
        }

        return $cart; // Retourne un panier valide
    }
     
    private function calculateSubTotalHT(array $cart): float
    {
        $subTotalHT = 0;
    
        foreach ($cart['products'] as $item) {
            if (isset($item['variant']['price'], $item['quantity'], $item['variant']['offVariant'])) {
                $priceTTC = $item['variant']['price'];
                $discount = $item['variant']['offVariant'] / 100;
                $priceAfterDiscountTTC = $priceTTC * (1 - $discount);
                $priceHT = $priceAfterDiscountTTC / 1.2; // Conversion TTC -> HT
                $subTotalHT += $priceHT * $item['quantity'];
    
                // Log pour déboguer
                error_log("Variant ID: {$item['variant']['id']} - Price HT: $priceHT - Quantity: {$item['quantity']}");
            } else {
                error_log('Item mal formé dans le panier: ' . json_encode($item));
            }
        }
    
        return $this->truncateToTwoDecimals($subTotalHT);
    }
    
    
    private function calculateSubTotalTTC(array $cart): float
    {
        $subTotalTTC = 0;
    
        foreach ($cart['products'] as $item) {
            if (isset($item['variant']['price'], $item['quantity'], $item['variant']['offVariant'])) {
                $priceTTC = $item['variant']['price'];
                $discount = $item['variant']['offVariant'] / 100;
                $priceAfterDiscountTTC = $priceTTC * (1 - $discount);
                $subTotalTTC += $priceAfterDiscountTTC * $item['quantity'];
    
                // Log pour déboguer
                error_log("Variant ID: {$item['variant']['id']} - Price TTC (après remise): $priceAfterDiscountTTC - Quantity: {$item['quantity']}");
            } else {
                error_log('Item mal formé dans le panier: ' . json_encode($item));
            }
        }
    
        return $this->truncateToTwoDecimals($subTotalTTC);
    }
    
    
    private function calculateTax(array $cart): float
    {
        $subTotalHT = $this->calculateSubTotalHT($cart);
        $subTotalTTC = $this->calculateSubTotalTTC($cart);
    
        $tax = $subTotalTTC - $subTotalHT; // TVA = TTC - HT
        return $this->truncateToTwoDecimals($tax);
    }
    
    private function calculateTotalTTC(array $cart): float
    {
        return $this->calculateSubTotalTTC($cart);
    }
    
    private function truncateToTwoDecimals(float $value): float
    {
        return round($value, 2); // Utilise round pour gérer les valeurs exactes
    }
    
    public function getFullCart(): array
    {
        $cart = $this->getCart();
        $fullCart = [
            'products' => [],
            'data' => [],
        ];
    
        foreach ($cart as $item) {
            $variant = $this->repoProductVariant->find($item['variantId']);
            if (!$variant) {
                error_log("Variante introuvable pour ID: {$item['variantId']}");
                continue;
            }
    
            $product = $variant->getProduct();
            $priceTTC = $variant->getPrice();
            $discount = $variant->getOffVariant() / 100;
            $priceAfterDiscountTTC = $priceTTC * (1 - $discount);
    
            error_log("Produit: {$product->getName()} - ID variante: {$variant->getId()} - Prix TTC: $priceTTC - Prix après remise: $priceAfterDiscountTTC");
    
            $fullCart['products'][] = [
                'product' => [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'slug' => $product->getSlug(),
                    'images' => array_map(
                        fn($img) => $img->getImageName(), 
                        $variant->getVariantImages()->toArray()
                    ),
                ],
                'variant' => [
                    'id' => $variant->getId(),
                    'price' => $variant->getPrice(),
                    'offVariant' => $variant->getOffVariant(),
                    'size' => $item['selectedSize'],
                    'color' => $item['selectedColor'],
                ],
                'quantity' => $item['quantity'],
            ];
            
        }
    
        $fullCart['data'] = [
            'cart_count' => count($cart),
            'subTotalHT' => $this->calculateSubTotalHT($fullCart),
            'Taxe' => $this->calculateTax($fullCart),
            'subTotalTTC' => $this->calculateSubTotalTTC($fullCart),
        ];
    
        return $fullCart;
    }
    
    


    /**
     * Récupérer la quantité totale de produits dans le panier
     */
    public function getCartQuantity(): int
    {
        $fullCart = $this->getFullCart();
        return $fullCart['data']['cart_count'];
    }
}

