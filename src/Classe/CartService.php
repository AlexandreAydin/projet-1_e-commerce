<?php

namespace App\Service;

use App\Entity\Cart;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductVariantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;

class CartService
{
    private $requestStack;
    private $repoProduct;
    private $security;
    private $cartRepository;
    private $repoProductVariant;
    
    private EntityManagerInterface $manager;
    private $tva = 0.2;

    public function __construct(RequestStack $requestStack,
    ProductRepository $repoProduct,
    CartRepository $cartRepository,
    EntityManagerInterface $manager,
    Security $security,
    ProductVariantRepository $repoProductVariant)
    {
        $this->requestStack = $requestStack;
        $this->security = $security;
        $this->cartRepository = $cartRepository;
        $this->manager = $manager;
        $this->repoProduct = $repoProduct;
        $this->repoProductVariant = $repoProductVariant;
        
    }



    public function getCartEntity(): ?Cart
    {
        $user = $this->security->getUser(); // Vérifie l'utilisateur connecté
        if (!$user) {
            return null; // Si aucun utilisateur n'est connecté
        }

        return $this->cartRepository->findOneBy(['user' => $user]);
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
    
        // Récupérer le stock en fonction de la taille
        $sizeStock = null;
        foreach ($variant->getSizes() as $sizeEntity) {
            if ($sizeEntity->getSize() === $size) {
                $sizeStock = $sizeEntity->getStock();
                break;
            }
        }
    
        if ($sizeStock === null) {
            throw new \Exception('Stock pour la taille spécifiée introuvable.');
        }
    
        // Vérification de la limite de stock
        $currentQuantity = 0;
        foreach ($cart as &$item) {
            if (
                $item['variantId'] === $variantId &&
                $item['selectedSize'] === $size &&
                $item['selectedColor'] === $color
            ) {
                $currentQuantity = $item['quantity'];
                break;
            }
        }
    
        if ($currentQuantity + $quantity > $sizeStock) {
            throw new \Exception('Quantité demandée supérieure au stock disponible.');
        }
    
        // Ajouter au panier
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
            'price' => $variant->getPrice(),
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

    public function updateCart($cartData): array
    {
        $cart = $this->getCart(); // Obtenez le panier actuel
        $this->saveCart($cartData); // Sauvegarder les changements dans le panier
    
        // Si le panier est vide, supprimer le code promo
        if (empty($cartData)) {
            $this->getSession()->remove('applied_coupon'); // Supprimez le code promo de la session
            $cartEntity = $this->getCartEntity();
            if ($cartEntity) {
                $cartEntity->setCouponApplied(false); // Réinitialiser l'état du coupon
                $this->manager->persist($cartEntity);
                $this->manager->flush();
            }
        }
    
        return $this->getFullCart();
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
    
    // public function getFullCart(): array
    // {
    //     $cart = $this->getCart();
    //     $fullCart = [
    //         'products' => [],
    //         'data' => [],
    //     ];
    
    //     foreach ($cart as $item) {
    //         $variant = $this->repoProductVariant->find($item['variantId']);
    //         if (!$variant) {
    //             error_log("Variante introuvable pour ID: {$item['variantId']}");
    //             continue;
    //         }
    
    //         $product = $variant->getProduct();
    //         $priceTTC = $variant->getPrice();
    //         $discount = $variant->getOffVariant() / 100;
    //         $priceAfterDiscountTTC = $priceTTC * (1 - $discount);
    
    //         error_log("Produit: {$product->getName()} - ID variante: {$variant->getId()} - Prix TTC: $priceTTC - Prix après remise: $priceAfterDiscountTTC");
    
    //         $fullCart['products'][] = [
    //             'product' => [
    //                 'id' => $product->getId(),
    //                 'name' => $product->getName(),
    //                 'slug' => $product->getSlug(),
    //                 'images' => array_map(
    //                     fn($img) => $img->getImageName(), 
    //                     $variant->getVariantImages()->toArray()
    //                 ),
    //             ],
    //             'variant' => [
    //                 'id' => $variant->getId(),
    //                 'price' => $variant->getPrice(),
    //                 'offVariant' => $variant->getOffVariant(),
    //                 'size' => $item['selectedSize'],
    //                 'color' => $item['selectedColor'],
    //             ],
    //             'quantity' => $item['quantity'],
    //         ];
            
    //     }
    
    //     $fullCart['data'] = [
    //         'cart_count' => count($cart),
    //         'subTotalHT' => $this->calculateSubTotalHT($fullCart),
    //         'Taxe' => $this->calculateTax($fullCart),
    //         'subTotalTTC' => $this->calculateSubTotalTTC($fullCart),
    //     ];
    
    //     return $fullCart;
    // }

    private function isCouponApplied(): bool
    {
        $cart = $this->getCartEntity(); // Supposons que cette méthode récupère l'entité du panier.
        return $cart && $cart->isCouponApplied(); // Vérifie si le coupon est appliqué (booléen dans l'entité `Cart`).
    }

    
    public function getFullCart(): array
    {
        $cart = $this->getCart(); // Récupérer le panier actuel
        $fullCart = [
            'products' => [],
            'data' => [],
        ];
    
        // Initialiser les totaux
        $totalTTCWithoutDiscount = 0; // Total TTC avant réduction
        $totalTTCWithBaseDiscount = 0; // Total TTC après réduction de base
        $promoDiscountAmount = 0; // Réduction appliquée par code promo
    
        foreach ($cart as $item) {
            $variant = $this->repoProductVariant->find($item['variantId']);
            if (!$variant) {
                continue; // Ignorer si la variante n'est pas trouvée
            }
    
            $product = $variant->getProduct();
    
            // Récupérer le stock pour la taille sélectionnée
            $stock = null;
            foreach ($variant->getSizes() as $sizeEntity) {
                if ($sizeEntity->getSize() === $item['selectedSize']) {
                    $stock = $sizeEntity->getStock();
                    break;
                }
            }
    
            // Par défaut, définir le stock à 0 si introuvable
            $stock = $stock ?? 0;
    
            // Calcul des prix
            $priceTTC = $variant->getPrice(); // Prix TTC avant réduction
            $baseDiscount = $variant->getOffVariant() / 100; // Réduction de base en pourcentage
            $priceAfterBaseDiscountTTC = $priceTTC * (1 - $baseDiscount); // Prix TTC après réduction de base
    
            // Ajouter au total TTC
            $totalTTCWithoutDiscount += $priceTTC * $item['quantity'];
            $totalTTCWithBaseDiscount += $priceAfterBaseDiscountTTC * $item['quantity'];
    
            // Ajouter le produit au tableau des produits détaillés
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
                    'stock' => $stock,
                ],
                'quantity' => $item['quantity'],
                'maxQuantityReached' => $item['quantity'] >= $stock, // Indique si la quantité atteint le stock
            ];
        }
    
        // Gestion des réductions par code promo
        $appliedCoupon = $this->getSession()->get('applied_coupon', null);
        if ($appliedCoupon) {
            $promoDiscountPercentage = $appliedCoupon['discountPercentage'] ?? 0;
            $promoDiscountAmount = $totalTTCWithBaseDiscount * ($promoDiscountPercentage / 100);
        }
    
        // Calcul final après application de toutes les réductions
        $finalTTC = $totalTTCWithBaseDiscount - $promoDiscountAmount;
        $promoDiscountPercentage = $appliedCoupon['discountPercentage'] ?? 0;
    
        // Ajouter les totaux dans la section 'data'
        $fullCart['data'] = [
            'cart_count' => count($cart),
            'subTotalHT' => $this->calculateSubTotalHT($fullCart) ?? 0, // Total HT (avec sécurité)
            'Taxe' => $this->calculateTax($fullCart) ?? 0, // Taxe calculée
            'subTotalTTCWithoutDiscount' => $totalTTCWithoutDiscount, // Total TTC avant réduction
            'subTotalTTCWithBaseDiscount' => $totalTTCWithBaseDiscount, // Total TTC après réduction de base
            'subTotalTTC' => $finalTTC, // Total TTC après toutes réductions
            'appliedDiscountAmount' => $promoDiscountAmount, // Montant de la réduction appliquée
            'appliedDiscountPercentage' => $promoDiscountPercentage,
            'isCouponApplied' => $this->isCouponApplied(),
        ];
    
        // Ajouter les informations du code promo
        $fullCart['appliedCouponCode'] = $appliedCoupon['code'] ?? 'Aucun';
        $fullCart['appliedDiscountAmount'] = $promoDiscountAmount;
    
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

