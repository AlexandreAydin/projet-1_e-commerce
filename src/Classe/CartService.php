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

    public function addToCart($id)
    {
        $cart = $this->getCart();
        // produit déjà dans le panier 
        if (isset($cart[$id])) {
            $cart[$id]++;
        // le produit n'est pas encore dans le panier
        } else {
            $cart[$id] = 1;
        }
        $this->updateCart($cart);

    }

    public function deleteFromCart($id)
    {
        $cart = $this->getCart();

        if (isset($cart[$id])) {
            // produit déjà dans le panier
            if ($cart[$id] > 1) {
                // produit existe plus d'une fois 
                $cart[$id]--;
            } else {
                unset($cart[$id]);
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

    public function updateCart($cart)
    {
        $this->getSession()->set('cart', $cart); // utilisé $this->getSession() au lieu de $this->session
        $this->getSession()->set('cartData', $this->getFullCart()); // utilisé $this->getSession() au lieu de $this->session
    }

    public function getCart()
    {
        return $this->getSession()->get('cart', []); // utilisé $this->getSession() au lieu de $this->session
    }
    

    public function getFullCart()
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
