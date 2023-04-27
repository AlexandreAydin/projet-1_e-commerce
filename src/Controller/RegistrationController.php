<?php

namespace App\Controller;

use App\Classe\Mail;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
 
    #[Route('/register', name: 'app_register')]
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
    
            // Générer une URL signée
            $signedUrl = $urlGenerator->generate(
                'app_verify_email',
                ['id' => $user->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
    
            // Envoyer un e-mail avec Mailjet
            $mail = new Mail();
            $to_email = $user->getEmail();
            $to_name = $user->getFirstName();
            $subject = 'Veuillez confirmer votre mail';
            $content = sprintf('Pour valider votre inscription, veuillez cliquer <a href="%s">ici</a>.', $signedUrl);
            $mail->send($to_email, $to_name, $subject, $content);
    
            return $this->redirectToRoute('app_home');
        }
    
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }



    #[Route('/verify/email', name: 'app_verify_email')]
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
