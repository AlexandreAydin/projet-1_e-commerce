<?php

namespace App\Controller\Account;

use App\Classe\Mail;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class RegistrationController extends AbstractController
{
 
    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
    
            $entityManager->persist($user);
            $entityManager->flush();
    
            // Envoyer un e-mail de bienvenue
            $mail = new Mail();
            $to_email = $user->getEmail();
            $to_name = $user->getFirstName();
            $subject = 'Bienvenue sur Amanoz !';
            
            $content = "Bonjour ".$to_name.",<br/>";
            $content .= "Bienvenue sur Amanoz ! Nous sommes ravis de vous compter parmi nos clients. Votre inscription a bien été prise en compte.<br/><br/>";
            $content .= "Parcourez nos différentes catégories pour découvrir nos produits phares et profitez de nos offres spéciales et de nos nouveautés régulièrement mises à jour.<br/><br/>";
            $content .= "N'hésitez pas à nous contacter si vous avez des questions ou besoin d'assistance lors de vos achats.<br/><br/>";
            $content .= "Bonne découverte et bons achats sur Amanoz !<br/>";
            $content .= "Cordialement,<br/>";
            $content .= "L'équipe de Amanoz";
    
            $mail->send($to_email, $to_name, $subject, $content);
            
            return $this->redirectToRoute('app_home');
        }
    
        return $this->render('pages/registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
    


    #[Route('/verifier-email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request,
    EntityManagerInterface $entityManager,
     UserRepository $userRepository): Response
    {
        $id = $request->get('id');
    
        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }
    
        $user = $userRepository->find($id);
    
        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }
    
        $user->setIsVerified(true);

        // Enregistrer les modifications dans la base de données
        $entityManager->persist($user);
        $entityManager->flush();

    
        // // Utilisation de votre classe Mail
        // $mail = new Mail();
        // $to_email = $user->getEmail();
        // $to_name = $user->getFirstName();
        // $subject = 'Test d\'e-mail';
        // $content = 'Ceci est un test d\'e-mail envoyé depuis Symfony';
        // $mail->send($to_email, $to_name, $subject, $content);
    
        $this->addFlash('success', 'Votre adresse e-mail a été vérifiée.');
    
        return $this->redirectToRoute('app_register');
    }

}
