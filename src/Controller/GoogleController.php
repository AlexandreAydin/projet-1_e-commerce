<?php

namespace App\Controller;

use App\Repository\GoogleOAuthSettingRepository;
use League\OAuth2\Client\Provider\Google;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class GoogleController extends AbstractController
{
    #[Route('/connexion/google', name: 'connect_google_start')]
    public function connectAction(
        Request $request,
        GoogleOAuthSettingRepository $googleRepo,
        RouterInterface $router
    ): Response {
        $setting = $googleRepo->findActive();

        if (!$setting || !$setting->getClientId() || !$setting->getClientSecret()) {
            $this->addFlash('error', 'La connexion Google est désactivée ou non configurée.');
            return $this->redirectToRoute('app_login');
        }

        $provider = new Google([
            'clientId'     => $setting->getClientId(),
            'clientSecret' => $setting->getClientSecret(),
            'redirectUri'  => $setting->getRedirectUri()
                ?? $router->generate('connect_google_check', [], RouterInterface::ABSOLUTE_URL),
        ]);

        $authUrl = $provider->getAuthorizationUrl([
            'scope' => ['openid', 'email', 'profile'],
        ]);

        // Stocker le state en session pour sécurité CSRF
        $request->getSession()->set('oauth2state', $provider->getState());

        return new RedirectResponse($authUrl);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(): Response
    {
        // Géré par GoogleAuthenticator — si on arrive ici, l'auth a réussi
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        return $this->redirectToRoute('app_home');
    }
}