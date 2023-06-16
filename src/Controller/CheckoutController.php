<?php

namespace App\Controller;

use App\Form\CheckoutType;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CheckoutController extends AbstractController
{
    #[Route('/caisse', name: 'app_checkout')]
    public function index(CartService $cartService, Request $request): Response
    {
        $user = $this->getUser();
        $cart = $cartService->getFullCart();

        if(!$cart){
            return $this->redirectToRoute("app_home");
        }
        
        if(!$user->getAddresses()->getValues()){
            $this->addFlash(
                'checkout_message',
                "Merci d'ajouter votre adressse avant de continuer"
            );
            return $this->redirectToRoute("app_address_new");
        }

        $form=$this->createForm(CheckoutType::class,null,['user'=>$user]);

        $form->handleRequest($request);

        return $this->render('pages/checkout/index.html.twig',[
            'cart'=> $cart,
            'checkout'=> $form->createView()
        ]);
    }
}
