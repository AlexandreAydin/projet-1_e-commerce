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
     
   
     
    public function getFullCart(): array
    {
        $cart = $this->getSession()->get('cart', []);
        $fullCart = ['products' => []];
        $cartCount = 0;
        $subTotalTTC = 0;
    
        foreach ($cart as $key => $item) {
            if (!isset($item['variantId'], $item['quantity'], $item['selectedSize'], $item['selectedColor'])) {
                continue;
            }
    
            $variant = $this->repoProductVariant->find($item['variantId']);
            if (!$variant) {
                continue;
            }
    
            $product = $variant->getProduct();
            if (!$product) {
                continue;
            }
    
            $price = $variant->getPrice();
            $itemTotalPrice = $price * $item['quantity'];
    
            $fullCart['products'][] = [
                'product' => [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'slug' => $product->getSlug(),
                    'images' => array_map(fn($image) => $image->getImageName(), $product->getImages()->toArray()),
                ],
                'variant' => [
                    'id' => $variant->getId(),
                    'price' => $price,
                    'size' => $item['selectedSize'],
                    'color' => $item['selectedColor'],
                ],
                'quantity' => $item['quantity'],
            ];
    
            $cartCount += $item['quantity'];
            $subTotalTTC += $itemTotalPrice;
        }
    
        $taxes = $subTotalTTC * $this->tva;
        $subTotalHT = $subTotalTTC - $taxes;
    
        return [
            'products' => $fullCart['products'],
            'data' => [
                'cart_count' => $cartCount,
                'subTotalHT' => round($subTotalHT, 2),
                'Taxe' => round($taxes, 2),
                'subTotalTTC' => round($subTotalTTC, 2),
            ],
        ];
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

