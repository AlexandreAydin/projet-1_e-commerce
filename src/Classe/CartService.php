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

    /**
     * Obtenir la session courante
     */
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
    
        foreach ($cart as &$item) {
            if (
                $item['variantId'] === $variantId &&
                $item['selectedSize'] === $size &&
                $item['selectedColor'] === $color
            ) {
                // Ajout de la quantité choisie
                $item['quantity'] += $quantity;
                $this->saveCart($cart);
                return;
            }
        }
    
        // Si la variante n'existe pas encore dans le panier, on l'ajoute
        $cart[] = [
            'variantId' => $variantId,
            'quantity' => $quantity, // Quantité correcte ici
            'selectedSize' => $size,
            'selectedColor' => $color,
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
     * Supprimer une quantité spécifique d'un produit du panier
     */
    // public function deleteFromCart(int $variantId, int $count = 1): void
    // {
    //     $cart = $this->getCart();
    
    //     // Assurez-vous que le panier est un tableau
    //     if (!is_array($cart)) {
    //         $cart = [];
    //     }
    
    //     // Parcourt les produits dans le panier
    //     foreach ($cart as $cartKey => $item) {
    //         if (isset($item['variantId']) && $item['variantId'] == $variantId) {
    //             // Réduit la quantité ou supprime le produit
    //             if ($item['quantity'] <= $count) {
    //                 unset($cart[$cartKey]); // Supprime complètement le produit
    //             } else {
    //                 $cart[$cartKey]['quantity'] -= $count; // Diminue la quantité
    //             }
    //             break;
    //         }
    //     }
    
    //     // Met à jour le panier dans la session
    //     $this->updateCart($cart);
    // }
    
    
    

    /**
     * Supprimer complètement un produit du panier
     */
    public function deleteAllFromCart($variantId)
{
    $cart = $this->getCart();

    // Rechercher et supprimer l'élément correspondant
    foreach ($cart as $key => $item) {
        if ($item['variantId'] == $variantId) {
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
     

















    /**
     * Calculer le panier complet avec les détails des produits, quantités et prix
     */



    //  public function getFullCart(): array
    //  {
    //      $cart = $this->getCart(); // Récupère le panier de la session
    //      $fullCart = ['products' => []];
    //      $cart_count = 0;
    //      $subTotalTTC = 0;
     
    //      foreach ($cart as $item) {
    //          $variantId = $item['variantId'];
    //          $quantity = $item['quantity'];
    //          $selectedSize = $item['selectedSize'];
    //          $selectedColor = $item['selectedColor'];
     
    //          $variant = $this->repoProductVariant->find($variantId);
    //          if (!$variant) {
    //              continue;
    //          }
     
    //          $product = $variant->getProduct();
    //          if (!$product) {
    //              continue;
    //          }
     
    //          // Vérifiez si ce produit existe déjà dans le `fullCart`
    //          $existingProductKey = array_search($variantId, array_column($fullCart['products'], 'variantId'));
     
    //          if ($existingProductKey !== false) {
    //              // Si le produit existe déjà, combinez les quantités
    //              $fullCart['products'][$existingProductKey]['quantity'] += $quantity;
    //          } else {
    //              // Sinon, ajoutez le produit comme une nouvelle entrée
    //              $fullCart['products'][] = [
    //                  'product' => [
    //                      'id' => $product->getId(),
    //                      'name' => $product->getName(),
    //                      'slug' => $product->getSlug(),
    //                      'images' => array_map(fn($image) => $image->getImageName(), $product->getImages()->toArray()),
    //                  ],
    //                  'variant' => [
    //                      'id' => $variant->getId(),
    //                      'price' => $variant->getPrice(),
    //                      'size' => $selectedSize,
    //                      'color' => $selectedColor,
    //                  ],
    //                  'quantity' => $quantity,
    //              ];
    //          }
     
    //          $cart_count += $quantity;
    //          $subTotalTTC += $variant->getPrice() * $quantity;
    //      }
     
    //      $taxes = $subTotalTTC * $this->tva;
    //      $subTotalHT = $subTotalTTC - $taxes;
     
    //      return [
    //          'products' => $fullCart['products'],
    //          'data' => [
    //              'cart_count' => $cart_count,
    //              'subTotalHT' => $subTotalHT,
    //              'Taxe' => $taxes,
    //              'subTotalTTC' => $subTotalTTC,
    //          ],
    //      ];
    //  }   
     
    private function calculateSubTotalHT(array $cart): float
    {
        $subTotalHT = 0;
    
        foreach ($cart['products'] as $item) {
            if (isset($item['variant']['price'], $item['quantity'], $item['variant']['offVariant'])) {
                $priceTTC = $item['variant']['price']; // Prix TTC
                $discount = $item['variant']['offVariant'] / 100; // Réduction
                $priceAfterDiscountTTC = $priceTTC * (1 - $discount); // Prix TTC après réduction
                $priceHT = $priceAfterDiscountTTC / 1.2; // Conversion TTC -> HT
                $subTotalHT += $priceHT * $item['quantity'];
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
                $discount = $item['variant']['offVariant'] / 100; // Réduction
                $priceAfterDiscountTTC = $priceTTC * (1 - $discount); // Prix TTC après réduction
                $subTotalTTC += $priceAfterDiscountTTC * $item['quantity'];
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
            $product = $variant->getProduct();
    
            $variantImages = [];
            foreach ($variant->getVariantImages() as $image) {
                $variantImages[] = '/uploads/products/' . $image->getImageName();
            }
    
            $fullCart['products'][] = [
                'product' => [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'slug' => $product->getSlug(),
                    'images' => $variantImages,
                ],
                'variant' => [
                    'id' => $variant->getId(),
                    'price' => $variant->getPrice(),
                    'offVariant' => $variant->getOffVariant(), // Réduction
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
            'subTotalTTC' => $this->calculateTotalTTC($fullCart),
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

