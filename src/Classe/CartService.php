<?php

namespace App\Service;

use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartService
{
    private $session;
    private $repoProduct;

    public function __construct(RequestStack $requestStack, ProductRepository $repoProduct)
    {
        $this->session = $requestStack->getSession();
        $this->repoProduct = $repoProduct;
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
        $this->session->set('cart', $cart);
        $this->session->set('cartData', $this->getFullCart());
    }

    public function getCart()
    {
        return $this->session->get('cart', []);
    }

    public function getFullCart()
    {
        $cart = $this->getCart();
        $fullCart = [];

        foreach ($cart as $id => $quantity) {
            $product = $this->repoProduct->find($id);
            if ($product) {
                // produit récupéré avec succès
                $fullCart[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                ];
            }else{
                $this->deleteFromCart($id);
            }
        }
        return $fullCart;
    }
}
