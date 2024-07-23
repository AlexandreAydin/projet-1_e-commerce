<?php

namespace App\Controller\Cart;

use App\Classe\OrderServices;
use App\Entity\User;
use App\Form\CheckoutType;
use App\Repository\AddressRepository;
use App\Repository\CarrierRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CheckoutController extends AbstractController
{
    #[Route('/caisse', name: 'app_checkout')]
    public function index(CartService $cartService,
    Request $request,
    SessionInterface $session,
    OrderServices $orderServices,
    CarrierRepository $carrierRepository,
     AddressRepository $addressRepository): Response
    {
        $cart = $cartService->getFullCart();

        if(!count($cart['products'])) {
            return $this->redirectToRoute('app_home');
        }
    
        if (!isset($cart['products'])) {
            return $this->redirectToRoute("app_home");
        }

        $user = $this->getUser();

        if (!$user) {
            // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
            return $this->redirectToRoute('app_login');
        }

        $addresses = $addressRepository->findByUser($user);
    
        // if (!$user->getAddresses()->getValues()) {
        //     $this->addFlash('checkout_message', "Merci d'ajouter votre adresse avant de continuer");
        //     return $this->redirectToRoute("app_address_new");
        // }

    
        $form = $this->createForm(CheckoutType::class, null, [
            'user' => $user,
        ]);
    
        return $this->render('pages/checkout/index.html.twig', [
            'cart' => $cart,
            'addresses' => $addresses,
            'checkout' => $form->createView(),
        ]);
    }
    

    #[Route('/caisse/confirmer', name:'app_checkout_confirm')]
    public function checkout_confirm(
    CartService $cartService,
    Request $request,
    SessionInterface $session,
    AddressRepository $addressRepository,
    OrderServices $orderServices
    ): Response
    {
        //on récupére le panier de l'utilisateur
        $cart = $cartService->getFullCart();

        if(!count($cart['products'])) {
            return $this->redirectToRoute('app_home');
        }
    
        if (!isset($cart['products'])) {
            return $this->redirectToRoute("app_home");
        }

        $user = $this->getUser();

        if (!$user) {
            // Si l'utilisateur n'est pas connecté, rediriger vers la page de connexion
            return $this->redirectToRoute('app_login');
        }
        
        $address = $addressRepository->findByUser($user);

        $form=$this->createForm(CheckoutType::class,null,['user'=>$user]);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()||$session->get('checkout_data')){
            // Pour envoyé au chekout si l'utilisateur décide de modifierl'adresse au dernier moments
            //  on ajout  ||$this->session->get('checkout_data' pour renvoyer l'uilisateur au chekckout
            //  faire le changement aussi sur addresse controller sur controller edit
            if($session->get('checkout_data')){
                $data = $session->get('checkout_data');
            }else{
                $data= $form->getData();
                $session->set('checkout_data',$data);
            }
            
            $address = $data['address'];
            $carrier = $data['carrier'];
            $information=$data['information'];

            // sauvegarder le panier 
            $cart['checkout']= $data;
           $reference = $orderServices->saveCart($cart, $user, $address);



            return $this->render('pages/checkout/confirm.html.twig',[
                'cart'=> $cart,
                'address'=>$address,
                'carrier'=>$carrier,
                'information'=>$information,
                'reference' => $reference,
                'checkout'=> $form->createView()
            ]);
        }

        return $this->redirectToRoute('app_checkout');
    }

    #[Route('/caisse/modifier', name:'app_checkout_edit')]
    public function checkoutEdit(SessionInterface $session): Response
    {
        $session->set('checkout_data',[]);
        return $this->redirectToRoute('app_checkout');
    }

}
