<?php

namespace App\Controller;

use App\Entity\Address;
use App\Form\AddressType;
use App\Repository\AddressRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/adresse')]
class AddressController extends AbstractController
{
    #[Route('/', name: 'app_address_index', methods: ['GET'])]
    public function index(AddressRepository $addressRepository): Response
    {
        return $this->render('pages/address/index.html.twig', [
            'addresses' => $addressRepository->findAll(),
        ]);
    }

    #[Route('/nouvelle-adresse', name: 'app_address_new', methods: ['GET', 'POST'])]
    public function new(Request $request, AddressRepository $addressRepository, CartService $cartService): Response
    {
        $address = new Address();
        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user=$this->getUser();
            $address->setUser($user);
            $addressRepository->save($address, true);
            
            if($cartService->getFullCart()){
                return $this->redirectToRoute("app_checkout");
            }

            $this->addFlash('address_message','votre adresse a été enregistrée');

            return $this->redirectToRoute('app_account', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pages/address/new.html.twig', [
            'address' => $address,
            'form' => $form->createView()
        ]);
    }


    #[Route('/{id}/modifier', name: 'app_address_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Address $address, AddressRepository $addressRepository,SessionInterface $session): Response
    {
        $form = $this->createForm(AddressType::class, $address);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $addressRepository->save($address, true);
            // Pour envoyé au chekout si l'utilisateur décide de modifierl'adresse au dernier moments
            if($session->get('checkout_data')){
                $data= $session->get('checkout_data');
                $data['address']=$address;
                $session->set('checkout_data',$data);
                return $this->redirectToRoute("app_checkout_confirm");
            }
            $this->addFlash('address_message','votre adresse a été modifier');
            return $this->redirectToRoute('app_account', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pages/address/edit.html.twig', [
            'address' => $address,
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}', name: 'app_address_delete', methods: ['POST'])]
    public function delete(Request $request, Address $address, AddressRepository $addressRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$address->getId(), $request->request->get('_token'))) {
            $addressRepository->remove($address, true);
        }
        $this->addFlash('address_message','votre adresse a été supprimer');
        return $this->redirectToRoute('app_account', [], Response::HTTP_SEE_OTHER);
    }
}
