<?php

namespace App\Controller\Account;

use App\Classe\Mail;
use App\Entity\ResetPassword;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Form\ResetPasswordType;

class ResetPasswordController extends AbstractController
{

    #[Route('/mot-de-passe-oublier', name: 'app_reset_password')]
    public function index(Request $request,EntityManagerInterface $entityManager): Response
    {
        if($this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        if ($request->get('email')) {
            $user = $entityManager->getRepository(User::class)->findOneByEmail($request->get('email'));

            if ($user) {
                // 1 : Enregistrer en base la demande de reset_password avec user, token, createdAt.
                $reset_password = new ResetPassword();
                $reset_password->setUser($user);
                $reset_password->setToken(uniqid());
                $reset_password->setCreatedAt(new \DateTimeImmutable());
                $entityManager->persist($reset_password);
                $entityManager->flush();

                // 2 : Envoyer un email à l'utilisateur avec un lien lui permettant de mettre à jour son mot de passe.
                $url = $this->generateUrl('app_update_password', [
                    'token' => $reset_password->getToken()
                ]);

                $content = "Bonjour ".$user->getFirstname()."<br/>Vous avez demandé à réinitialiser votre mot de passe sur le site Yilmi Market.<br/><br/>";
                $content .= "Merci de bien vouloir cliquer sur le lien suivant pour <a href='".$url."'>mettre à jour votre mot de passe</a>.";

                $mail = new Mail();
                $mail->send($user->getEmail(), $user->getFirstname().' '.$user->getLastname(), 'Réinitialiser votre mot de passe sur sweetdate', $content);

                $this->addFlash('notice', 'Vous allez recevoir dans quelques secondes un mail avec la procédure pour réinitialiser votre mot de passe.');
            } else {
                $this->addFlash('notice', 'Cette adresse email est inconnue.');
            }
        }

        return $this->render('pages/reset_password/index.html.twig', [
            'controller_name' => 'ResetPasswordController',
        ]);
    }


    #[Route('/modifier-mon-mot-de-passe/{token}', name: 'app_update_password')]
    public function update(
    Request $request,
    UserPasswordHasherInterface $hasher, 
    $token,
    EntityManagerInterface $entityManager
    ): Response
    
     {

        if($this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        
        $reset_password = $entityManager->getRepository(ResetPassword::class)->findOneByToken($token);

         if (!$reset_password) {
             return $this->redirectToRoute('app_reset_password');
         }
        

        // Vérifier si le createdAt = now - 3h
        $now = new \DateTime();
        if ($now > $reset_password->getCreatedAt()->modify('+ 3 hour')) { 
            $this->addFlash('notice', 'Votre demande de mot de passe a expiré. Merci de la renouveller.');
            return $this->redirectToRoute('app_reset_password');
        }

        // Rendre une vue avec mot de passe et confirmez votre mot de passe.
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $new_pwd = $form->get('new_password')->getData();

            // Encodage des mots de passe
            $password = $hasher->hashPassword($reset_password->getUser(), $new_pwd);
            $reset_password->getUser()->setPassword($password);

            // Flush en base de donnée.
            $entityManager->flush();

            // Redirection de l'utilisateur vers la page de connexion.
            $this->addFlash('notice', 'Votre mot de passe a bien été mis à jour.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('pages/reset_password/update.html.twig', [
            'form' => $form->createView()
        ]);
    }


}
