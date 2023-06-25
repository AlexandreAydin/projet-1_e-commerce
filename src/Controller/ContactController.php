<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Repository\ContactRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/contact')]
class ContactController extends AbstractController
{
    #[Route('/', name: 'app_contact', methods: ['GET', 'POST'])]
    public function new(Request $request, ContactRepository $contactRepository): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contactRepository->save($contact, true);

            // Envoi d'email


              // on vide le formulaire apré avoir validé le formulaire
            $contact = new Contact();
            $form = $this->createForm(ContactType::class, $contact);
            $this->addFlash(
                'contact_success',
                'Votre message a bien été envoyer'
            );

           
            if ($form->isSubmitted() && !$form->isValid()) {
                $this->addFlash(
                    'contact_error',
                    'le formulaire contient des erreurs. Veuillez corriger et réessayer'
                );
            }
        }

        return $this->renderForm('contact/new.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

}
