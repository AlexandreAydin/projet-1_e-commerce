<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;


class CartService
{
    private $requestStack;
    private $repoProduct;
    private $tva = 0.2;

    public function __construct(RequestStack $requestStack, ProductRepository $repoProduct)
    {
        $this->requestStack = $requestStack;
        $this->repoProduct = $repoProduct;
    }

    private function getSession() // nouvelle méthode pour obtenir la session
    {
        return $this->requestStack->getSession();
    }

    // public function addToCart($id)
    // {
    //     $cart = $this->getCart();
    //     // produit déjà dans le panier 
    //     if (isset($cart[$id])) {
    //         $cart[$id]++;
    //     // le produit n'est pas encore dans le panier
    //     } else {
    //         $cart[$id] = 1;
    //     }
    //     $this->updateCart($cart);

    // }
    public function addToCart($id, $count = 1)
    {
        $cart = $this->getCart();
        // produit déjà dans le panier 
        if (!empty($cart[$id])) {
            $cart[$id] += $count;
        // le produit n'est pas encore dans le panier
        } else {
            $cart[$id] = $count;
        }
        $this->updateCart($cart);
    }

    // public function deleteFromCart($id)
    // {
    //     $cart = $this->getCart();

    //     if (isset($cart[$id])) {
    //         // produit déjà dans le panier
    //         if ($cart[$id] > 1) {
    //             // produit existe plus d'une fois 
    //             $cart[$id]--;
    //         } else {
    //             unset($cart[$id]);
    //         }
    //         $this->updateCart($cart);
    //     }
    // }

    public function deleteFromCart($id, $count = 1)
    {
        $cart = $this->getCart();

        if (isset($cart[$id])) {
            // produit déjà dans le panier
            if ($cart[$id] <= $count) {
                // produit existe plus d'une fois 
                unset($cart[$id]);
            } else {
                $cart[$id] -= $count;
            }
            $this->updateCart($cart);
        }
    }

    public function deleteAllFromCart($id)
    {
        $cart = $this->getCart();

        if (isset($cart[$id])) {
            // produit déjà dans le panier
            unset($cart[$id]);
            $this->updateCart($cart);
        }
    }

    public function deleteCart()
    {
        $this->updateCart([]);
    }
    

    // public function updateCart($cart)
    // {
    //     $this->getSession()->set('cart', $cart); // utilisé $this->getSession() au lieu de $this->session
    //     $this->getSession()->set('cartData', $this->getFullCart()); // utilisé $this->getSession() au lieu de $this->session
    // }

    // public function updateCart($cart)
    // {
    //     // Sauvegarde dans la session
    //     $this->getSession()->set('cart', $cart);
    //     $this->getSession()->set('cartData', $this->getFullCart());

    //     // Sauvegarde dans localStorage (côté client)
    //     // echo "<script>
    //     //     localStorage.setItem('cart', '" . json_encode($cart) . "');
    //     // </script>";
    // }

    public function updateCart($cart)
    {
        // Sauvegarde dans la session
        $this->getSession()->set('cart', $cart);
        $this->getSession()->set('cartData', $this->getFullCart());

    }


    // public function getCart()
    // {
    //     return $this->getSession()->get('cart', []); // utilisé $this->getSession() au lieu de $this->session
    // }
    public function getCart()
    {
        return $this->getSession()->get('cart', []); // utilisé $this->getSession() au lieu de $this->session
    }

    // public function getFullCart()
    // {
    //     $cart = $this->getCart();
    //     $fullCart = [];
    //     $fullCart["products"] = [];
    //     $cart_count = 0;
    //     $subTotalTTC = 0;  // Total TTC des produits
    
    //     foreach ($cart as $id => $quantity) {
    //         $product = $this->repoProduct->find($id);
    //         if ($product) {
    //             if ($quantity > $product->getQuantity()) {
    //                 $quantity = $product->getQuantity();
    //                 $cart[$id]= $quantity;
    //                 $this->updateCart($cart);
    //             }
    //             $fullCart["products"][] = [
    //                 'product' => [ 
    //                     'id' => $product->getId(),
    //                     'name' => $product->getName(),
    //                     'slug' => $product->getSlug(),
    //                     'images' => $product->getImages()->first()->getImageName(),
    //                     'price' => $product->getPrice()
    //                 ],
    //                 'id' => $id,
    //                 'quantity' => $quantity
    //             ];            
    //             $cart_count += $quantity;
    //             $subTotalTTC += ($product->getPrice() / 100) * $quantity;
    //         } else {
    //             $this->deleteFromCart($id);
    //         }
    //     }
        
    //     $taxes = ($subTotalTTC / 1.20) * 0.20;
    //     $subTotalHT = $subTotalTTC - $taxes;
    
    //     $fullCart = [
    //         'products' => $fullCart["products"],
    //         'data' => [
    //             "cart_count" => $cart_count,
    //             "subTotalHT" => $subTotalHT,
    //             "Taxe" => $taxes,
    //             "subTotalTTC" => $subTotalTTC
    //         ]
    //     ];
    
    //     return $fullCart;
    // }
      
    public function getFullCart() {
        $cart = $this->getCart();
        $fullCart = [];
        $fullCart["products"] = [];
        $cart_count = 0;
        $subTotalTTC = 0;  // Total TTC des produits
    
        foreach ($cart as $id => $quantity) {
            $product = $this->repoProduct->find($id);
            if ($product) {
                if ($quantity > $product->getQuantity()) {
                    $quantity = $product->getQuantity();
                    $cart[$id] = $quantity;
                    $this->updateCart($cart);
                }
    
            // Calculez le prix avec réduction s'il y en a une
            // Calculez le prix avec réduction s'il y en a une
            $productPrice = $product->getPrice();
            if ($product->getOff()) {
                $discount = $product->getOff() / 100; // Convertir le pourcentage en décimal
                $productPrice = $productPrice * (1 - $discount);
            }

    
            $fullCart["products"][] = [
                'product' => [ 
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'slug' => $product->getSlug(),
                    'images' => $product->getImages()->first()->getImageName(),
                    'price' => $productPrice
                ],
                'id' => $id,
                'quantity' => $quantity
            ];            
    
                $cart_count += $quantity;
                $subTotalTTC += ($productPrice / 100) * $quantity;
            } else {
                $this->deleteFromCart($id);
            }
        }
        
        $taxes = ($subTotalTTC / 1.20) * 0.20;
        $subTotalHT = $subTotalTTC - $taxes;
    
        $fullCart = [
            'products' => $fullCart["products"],
            'data' => [
                "cart_count" => $cart_count,
                "subTotalHT" => $subTotalHT,
                "Taxe" => $taxes,
                "subTotalTTC" => $subTotalTTC
            ]
        ];
    
        return $fullCart;
    }
    

    public function getFullFavoris()
    {
        $cart = $this->getCart();
        $fullCart = [];
        $fullCart["products"] = [];
        $quantity_cart  = 0;
        $subTotal = 0;
    
        foreach ($cart as $id => $quantity) {
            $product = $this->repoProduct->find($id);
            if ($product) {
                // produit récupéré avec succès
                if($quantity > $product->getQuantity()){
                    $quantity = $product->getQuantity();
                    $cart[$id]= $quantity;
                    $this->updateCart($cart);
                }
                $fullCart["products"][] = [
                    'product' => $product,
                    'id' => $id,
                    'quantity' => $quantity,
                ];
                $quantity_cart += $quantity;
                $subTotal += $product->getPrice()/100 * $quantity;
            } else {
                $this->deleteFromCart($id);
            }
        }
        
        $taxes = round($subTotal * $this->tva, 2);
        $subTotalTTC = round(($subTotal + $taxes), 2);

        $fullCart = [
            'products' => $fullCart["products"],
            'data' => [
                "quantity_cart" => $quantity_cart,
                "subTotalHT" => $subTotal,
                "Taxe" => $taxes,
                "subTotalTTC" => $subTotalTTC
            ]
        ];
        return $fullCart;

    }

    public function getCartQuantity() 
    {
        $fullCart = $this->getFullCart();
        return $fullCart['data']['quantity_cart'];
    }
    
}
