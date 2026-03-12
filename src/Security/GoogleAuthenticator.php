<?php 

namespace App\Security;

use App\Entity\User;
use App\Repository\GoogleOAuthSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\Google;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Bundle\SecurityBundle\Security;

class GoogleAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RouterInterface $router,
        private GoogleOAuthSettingRepository $googleRepo
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/connect/google/check'
            && $request->isMethod('GET')
            && $request->query->has('code');
    }

    public function authenticate(Request $request): Passport
    {
        $setting = $this->googleRepo->findActive();

        if (!$setting || !$setting->getClientId() || !$setting->getClientSecret()) {
            throw new AuthenticationException('Google OAuth non configuré ou désactivé dans l\'administration.');
        }

        // Construire le provider Google directement depuis la BDD
        $provider = new Google([
            'clientId'     => $setting->getClientId(),
            'clientSecret' => $setting->getClientSecret(),
            'redirectUri'  => $setting->getRedirectUri()
                ?? $this->router->generate('connect_google_check', [], RouterInterface::ABSOLUTE_URL),
        ]);

        $code        = $request->query->get('code');
        $accessToken = $provider->getAccessToken('authorization_code', ['code' => $code]);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function() use ($accessToken, $provider) {
                /** @var \League\OAuth2\Client\Provider\GoogleUser $googleUser */
                $googleUser = $provider->getResourceOwner($accessToken);
                $email      = $googleUser->getEmail();

                // 1) Déjà connecté avec Google ?
                $user = $this->em->getRepository(User::class)
                    ->findOneBy(['GoogleId' => $googleUser->getId()]);
                if ($user) return $user;

                // 2) Email déjà connu ?
                $user = $this->em->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                // 3) Créer un nouvel utilisateur
                if (!$user) {
                    $user = new User();
                    $user->setEmail($email);
                    $user->setFirstName($googleUser->getFirstName() ?? '');
                    $user->setLastName($googleUser->getLastName() ?? '');
                    $user->setPassword(sha1(random_bytes(10)));
                    $this->em->persist($user);
                    $this->em->flush();
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->router->generate('app_home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        return new RedirectResponse($this->router->generate('app_login'));
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse('/', Response::HTTP_TEMPORARY_REDIRECT);
    }
}