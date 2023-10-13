<?php

namespace App\Controller\Cart;

use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    private $cartServices;

    public function __construct (CartService $cartServices)
    {
        $this->cartServices = $cartServices;
    }

    #[Route('/panier', name: 'app_cart')]
    public function index(): Response
    {
        $cart= $this->cartServices->getFullCart();
        if(!$cart['products']){
            return $this->redirectToRoute("app_home");
        }
        $cart_json = json_encode($cart);

        return $this->render('pages/cart/index.html.twig', [
            'cart'=>$cart,
            'cart_json'=>$cart_json,
        ]);
    }


    #[Route('/panier/{id}/ajouter', name: 'app_add_to_cart')]
    public function add($id,$count = 1): Response
    {
        $this->cartServices->addToCart($id,$count);
        $cart= $this->cartServices->getFullCart();

        return $this ->json($cart);
    }

    #[Route('/panier/{id}/ajouter/{count}', name: 'app_add_to_cart_count')]
    public function addcount($id,$count = 1): Response
    {
        $this->cartServices->addToCart($id,$count);
        $cart= $this->cartServices->getFullCart();

        return $this ->json($cart);
    }

    #[Route('/mon-panier/{id}/diminuer', name: 'app_delete_to_cart')]
    public function deletFromCart($id): Response
    {
        $this->cartServices->deleteFromCart($id);
        $cart= $this->cartServices->getFullCart();

        return $this ->json($cart);
    }

    
    #[Route('/mon-panier/{id}/supprimer', name: 'app_cart_delete')]
    public function cart_delete_all($id): Response
    {
        $this->cartServices->deleteFromCart($id);
        $cart= $this->cartServices->getFullCart();

        return $this ->json($cart);
    }

    #[Route('/mon-panier/{id}/tout-supprimer', name: 'app_cart_delete_all')]
    public function deletAllToCart($id): Response
    {
        $this->cartServices->deleteAllFromCart($id);
        $cart= $this->cartServices->getFullCart();

        return $this ->json($cart);
    }


    #[Route('/mon-panier/obtenir', name: 'app_get_cart')]
    public function getCart(): Response
    {
        $cart= $this->cartServices->getFullCart();

        return $this ->json($cart);
    }

}