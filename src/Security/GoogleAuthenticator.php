<?php

namespace App\Security;

use App\Entity\Patient;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GoogleAuthenticator extends OAuth2Authenticator
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private ?UrlGeneratorInterface $urlGenerator = null;

    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        ?UrlGeneratorInterface $urlGenerator = null
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->urlGenerator = $urlGenerator;
    }

    public function supports(Request $request): ?bool
    {
        // Continue only if the current route is the Google callback
        return $request->attributes->get('_route') === 'app_connect_google_check';
    }

    public function authenticate(Request $request): Passport
    {
        $client = $this->getClient();
        $accessToken = $this->fetchAccessToken($client);

        return new SelfValidatingPassport(
            new UserBadge($accessToken->getToken(), function (string $token) use ($client, $accessToken) {
                try {
                    /** @var GoogleUser $googleUser */
                    $googleUser = $client->fetchUserFromToken($accessToken);

                    // Get user email from Google
                    $email = $googleUser->getEmail();
                    
                    if (!$email) {
                        throw new \Exception('Unable to get email from Google account');
                    }
                    
                    // Find existing user by email or googleId
                    $user = $this->entityManager->getRepository(User::class)->findOneBy([
                        'email' => $email,
                    ]);

                    if (!$user) {
                        // Try to find by googleId
                        $googleId = $googleUser->getId();
                        $user = $this->entityManager->getRepository(User::class)->findOneBy([
                            'googleId' => $googleId,
                        ]);
                    }

                    if (!$user) {
                        // Create new patient account
                        $user = new Patient();
                        $user->setEmail($email);
                        
                        // Set default role (Patient)
                        // Note: The discriminator map will handle this automatically
                    }

                    // Update user with Google info
                    $user->setGoogleId($googleUser->getId());
                    $user->setFirstName($googleUser->getFirstName() ?? 'Google');
                    $user->setLastName($googleUser->getLastName() ?? 'User');
                    
                    // Set avatar from Google if available
                    $picture = $googleUser->getAvatar();
                    if ($picture) {
                        $user->setAvatarUrl($picture);
                    }

                    // Mark email as verified (Google already verified)
                    $user->setIsEmailVerified(true);
                    
                    // Set a random password (not used for OAuth login)
                    if (!$user->getPassword()) {
                        $user->setPassword(bin2hex(random_bytes(32)));
                    }

                    // Save user
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    return $user;
                } catch (\Exception $e) {
                    throw new \RuntimeException('Failed to authenticate with Google: ' . $e->getMessage());
                }
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Get the user
        $user = $token->getUser();
        
        // Reset login attempts on successful login
        if ($user instanceof User) {
            $user->setLoginAttempts(0);
            $user->setLockedUntil(null);
            $user->setLastLoginAt(new \DateTime());
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            // Check if 2FA is enabled - if so, redirect to 2FA verification
            if ($user->isTotpAuthenticationEnabled()) {
                // Clear any previous target path to prevent redirect loops
                $session = $request->getSession();
                if ($session && $this->urlGenerator) {
                    $session->remove('_security.main.target_path');
                }
                
                // Redirect to 2FA verification
                if ($this->urlGenerator) {
                    return new RedirectResponse($this->urlGenerator->generate('app_2fa_verify'));
                }
            }
        }
        
        // Redirect based on user role
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles)) {
            $targetUrl = $this->router->generate('admin_trail_analytics');
        } elseif (in_array('ROLE_MEDECIN', $roles)) {
            $targetUrl = $this->router->generate('doctor_patient_queue_page');
        } elseif (in_array('ROLE_COACH', $roles)) {
            $targetUrl = $this->router->generate('coach_clients');
        } elseif (in_array('ROLE_NUTRITIONIST', $roles)) {
            $targetUrl = $this->router->generate('nutrisioniste_dash');
        } else {
            // Default: patient dashboard
            $targetUrl = $this->router->generate('appointment_patient_dashboard');
        }
        
        return new RedirectResponse($targetUrl);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $message = strtr($exception->getMessageKey(), $exception->getMessageData());

        // Add flash message
        $request->getSession()->getFlashBag()->add('error', 'Google login failed: ' . $message);

        // Redirect to login page
        $loginUrl = $this->router->generate('app_login');
        
        return new RedirectResponse($loginUrl);
    }

    private function getClient(): OAuth2ClientInterface
    {
        $client = $this->clientRegistry->getClient('google');
        
        // Get the underlying OAuth2 provider and set a custom HTTP client with SSL verification disabled
        $provider = $client->getOAuth2Provider();
        
        // Create a new HTTP client with SSL verification disabled
        $httpClient = new Client([
            'verify' => false,
        ]);
        
        // Replace the HTTP client in the provider if the method exists
        if (method_exists($provider, 'setHttpClient')) {
            $provider->setHttpClient($httpClient);
        }
        
        return $client;
    }
}
