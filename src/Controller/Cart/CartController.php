<?php

namespace App\Controller\Cart;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
        
    
        return $this->render('pages/cart/index.html.twig', [
            'cart'=>$cart
        ]);
    }


    #[Route('/panier/{id}/ajouter', name: 'app_add_to_cart')]
    public function add($id,): Response
    {
        $this->cartServices->addToCart($id);
        return $this ->redirectToRoute('app_cart');

    }

    #[Route('/mon-panier/{id}/diminuer', name: 'app_delete_to_cart')]
    public function deletFromCart($id): Response
    {
        $this->cartServices->deleteFromCart($id);
    
        return $this ->redirectToRoute('app_cart');
    }

    
    #[Route('/mon-panier/{id}/supprimer', name: 'app_cart_delete')]
    public function cart_delete_all($id): Response
    {
        $this->cartServices->deleteFromCart($id);
        return $this ->redirectToRoute('app_cart');
    }

    #[Route('/mon-panier/{id}/tout-supprimer', name: 'app_cart_delete_all')]
    public function deletAllToCart($id): Response
    {
        $this->cartServices->deleteAllFromCart($id);
        return $this ->redirectToRoute('app_cart');
    }




}