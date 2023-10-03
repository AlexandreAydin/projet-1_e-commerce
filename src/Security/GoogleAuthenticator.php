<?php 

// namespace App\Security;

// use App\Entity\User;
// use Doctrine\ORM\EntityManagerInterface;
// use Symfony\Component\Routing\RouterInterface;
// use Symfony\Component\HttpFoundation\Request;
// use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
// use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
// use League\OAuth2\Client\Provider\GoogleUser;
// use Symfony\Component\Security\Core\User\UserProviderInterface;
// use Symfony\Component\HttpFoundation\Response;
// use Symfony\Component\HttpFoundation\RedirectResponse;


// class GoogleAuthenticator extends SocialAuthenticator
// {
//     private $clientRegistry;
//     private $em;
//     private $router;

//     public function __construct( ClientRegistry $clientRegistry,EntityManagerInterface $em, RouterInterface $router)
//     {
//         $this->clientRegistry = $clientRegistry;
//         $this->em = $em;
//         $this->router = $router;
//     }

//     public function supports(Request $request): ?bool
//     {
//         // continue ONLY if the current ROUTE matches the check ROUTE
//         return $request->getPathInfo()=='/connect/google/check' && $request->isMethod('GET');
//     }

//     public function getCredentials(Request $request)
//     {
//         return $this->fetchAccessToken($this->getGoogleClient());
//     }

//     public function getUser($credentials,UserProviderInterface $userProvider)
//     {
//         /** @var GoogleUser $googleUser */
//         $googleUser= $this->getGoogleClient()
//             ->fetchUserFromToken($credentials);

//         $email = $googleUser->getEmail();

//         $user = $this->em->getRepository("App:User")
//             ->findOneBy(['email'=>$email]);
//         if (!$user){
//             $user = new User();
//             $user->setEmail($googleUser->getEmail());
//             $user->setFirstName($googleUser->getFirstName());
//             $user->setLastName($googleUser->getLastName());
//             $this->em->persist($user);
//             $this->em->flush();
//         }

//         return $user;
//     }

//     private function getGoogleClient()
//     {
//         return $this->clientRegistry
//             ->getClient('google');
//     }

//     public function start(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $authException): Response
//     {
//         return new RedirectResponse('/connexion');
//     }

//     public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $authException): Response
//     {
       
//     }

//     public function onAuthenticationSuccess(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $authException): Response
//     {
       
//     }


// }


// declare(strict_types=1);

// namespace App\Security;

// use App\Entity\User;
// use Doctrine\ORM\EntityManagerInterface;
// use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\Security\Core\Exception\AuthenticationException;
// use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
// use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
// use League\OAuth2\Client\Provider\GoogleUser;
// use Symfony\Component\Routing\RouterInterface;
// use Symfony\Component\Security\Core\User\UserProviderInterface;
// use Symfony\Component\HttpFoundation\Response;
// use Symfony\Component\HttpFoundation\RedirectResponse;
// use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
// use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
// use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
// use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
// use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
// use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
// use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;


// class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
// {
//     public function __construct(
//         private ClientRegistry $clientRegistry,
//         private EntityManagerInterface $em,
//         private RouterInterface $router
//     ) {}

//     public function supports(Request $request): bool
//     {
//         return $request->getPathInfo() === '/connect/google/check' && $request->isMethod('GET');
//     }

//     public function getCredentials(Request $request): mixed
//     {
//         return $this->fetchAccessToken($this->getGoogleClient());
//     }

//     public function getUser(mixed $credentials, UserProviderInterface $userProvider): ?User
//     {
//         $googleUser = $this->getGoogleClient()->fetchUserFromToken($credentials);

//         $email = $googleUser->getEmail();

//         $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

//         if (!$user) {
//             $user = new User();
//             $user->setEmail($googleUser->getEmail());
//             $user->setFirstName($googleUser->getFirstName());
//             $user->setLastName($googleUser->getLastName());
//             $this->em->persist($user);
//             $this->em->flush();
//         }

//         return $user;
//     }

//     private function getGoogleClient(): object
//     {
//         return $this->clientRegistry->getClient('google');
//     }

//     public function start(Request $request, AuthenticationException $authException): Response
//     {
//         return new RedirectResponse('/connexion');
//     }

//     public function onAuthenticationFailure(Request $request, AuthenticationException $authException): Response
//     {
//         return new RedirectResponse('/connexion');
//     }
    
//     public function onAuthenticationSuccess(Request $request, AuthenticationException $authException): Response
//     {
//         return new RedirectResponse('/home');
//     }
    
// }





namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;


class GoogleAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{
    private $clientRegistry;
    private $entityManager;
    private $router;

    public function __construct(ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $client) {
                /** @var GoogleUser $googleUser */
                $googleUser = $client->fetchUserFromToken($accessToken);

                $email = $googleUser->getEmail();

                // 1) have they logged in withGoogle before? Easy!
                $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['GoogleId' => $googleUser->getId()]);

                if ($existingUser) {
                    return $existingUser;
                }

                // 2) do we have a matching user by email?
                $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

                // 3) Maybe you just want to "register" them by creating
                // a User object
                if (!$user) {
                    $user = new User();
                    $user->setEmail($googleUser->getEmail());
                    $user->setFirstName($googleUser->getFirstName());
                    $user->setLastName($googleUser->getLastName());
                    $user->setPassword(sha1(random_bytes(10))); // génère un mot de passe fictif
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                }
                    

                return $user;
            })
        );
    }

    public function supports(Request $request): ?bool
    {
         // continue ONLY if the current ROUTE matches the check ROUTE
            return $request->getPathInfo()=='/connect/google/check' && $request->isMethod('GET');
    }
    

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // change "app_homepage" to some route in your app
        $targetUrl = $this->router->generate('app_home');

        return new RedirectResponse($targetUrl);
    
        // or, on success, let the request continue to be handled by the controller
        //return null;
    }

    public function getUser(mixed $credentials, UserProviderInterface $userProvider): ?User
    {
        $googleUser = $this->getGoogleClient()->fetchUserFromToken($credentials);

        $email = $googleUser->getEmail();

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($googleUser->getEmail());
            $user->setFirstName($googleUser->getFirstName());
            $user->setLastName($googleUser->getLastName());
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        return $user;
    }

    private function getGoogleClient(): object
    {
        return $this->clientRegistry->getClient('google');
    }

   /**
     * Called when authentication is needed, but it's not sent.
     * This redirects to the 'login'.
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            '/', // might be the site, where users choose their oauth provider
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Stockez l'objet exception directement.
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
    
        return new RedirectResponse($this->router->generate('app_login'));
    }
    
}
