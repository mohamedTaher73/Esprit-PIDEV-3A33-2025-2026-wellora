<?php

namespace App\Security;

use App\Entity\User;
use App\Service\CaptchaService;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class Authenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
        private CaptchaService $captchaService
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $captchaCode = $request->request->get('captcha_code', '');

        // ========== CAPTCHA VALIDATION ==========
        if (empty($captchaCode)) {
            throw new CustomUserMessageAuthenticationException('Veuillez entrer le code de vérification.');
        }
        
        if (!$this->captchaService->validate($captchaCode)) {
            throw new CustomUserMessageAuthenticationException('Le code de vérification est incorrect. Veuillez réessayer.');
        }
        // ========== END CAPTCHA VALIDATION ==========

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email, function ($email) {
                $user = $this->entityManager->getRepository(User::class)->findOneByEmail($email);
                
        if (!$user) {
                    throw new CustomUserMessageAuthenticationException('Email ou mot de passe incorrect.');
                }
                
                // Check if professional is verified by admin
                $roles = $user->getRoles();
                $professionalRoles = ['ROLE_MEDECIN', 'ROLE_COACH', 'ROLE_NUTRITIONIST'];
                $hasProfessionalRole = !empty(array_intersect($roles, $professionalRoles));
                
                // For administrators, no verification check needed
                if (in_array('ROLE_ADMIN', $roles)) {
                    return $user;
                }
                
                // Check if email is verified (for ALL user types)
                if (!$user->isEmailVerified()) {
                    throw new CustomUserMessageAuthenticationException(
                        'Veuillez vérifier votre adresse email avant de vous connecter.'
                    );
                }
                
                // For professionals, check admin verification status
                if ($hasProfessionalRole && method_exists($user, 'isVerifiedByAdmin')) {
                    if (!$user->isVerifiedByAdmin()) {
                        throw new CustomUserMessageAuthenticationException(
                            'Votre compte ' . strtolower(str_replace('ROLE_', '', $roles[0] ?? 'professionnel')) . 
                            ' est en attente de vérification par l\'administrateur.'
                        );
                    }
                }
                
                return $user;
            }),
            new PasswordCredentials($password),
            [
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();
        
        if ($user instanceof User) {
            // Reset login attempts on successful login
            $user->setLoginAttempts(0);
            $user->setLockedUntil(null);
            $user->setLastLoginAt(new \DateTime());
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            
            // Check if 2FA is enabled - if so, redirect to 2FA verification
            if ($user->isTotpAuthenticationEnabled()) {
                // Clear any previous target path to prevent redirect loops
                $session = $request->getSession();
                if ($session) {
                    $session->remove('_security.main.target_path');
                }
                
                // Redirect to 2FA verification
                return new RedirectResponse($this->urlGenerator->generate('app_2fa_verify'));
            }
        }

        if ($targetUrl = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetUrl);
        }

        // Redirect based on user role
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles)) {
            return new RedirectResponse($this->urlGenerator->generate('admin_trail_analytics'));
        }
        if (in_array('ROLE_MEDECIN', $roles)) {
            return new RedirectResponse($this->urlGenerator->generate('doctor_patient_queue_page'));
        }
        if (in_array('ROLE_COACH', $roles)) {
            return new RedirectResponse($this->urlGenerator->generate('coach_clients'));
        }
        if (in_array('ROLE_NUTRITIONIST', $roles)) {
            return new RedirectResponse($this->urlGenerator->generate('nutrisioniste_dash'));
        }
        
        // Default: patient dashboard
        return new RedirectResponse($this->urlGenerator->generate('appointment_patient_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, \Symfony\Component\Security\Core\Exception\AuthenticationException $exception): Response
    {
        $email = $request->request->get('email');
        
        if ($email) {
            $user = $this->entityManager->getRepository(User::class)->findOneByEmail($email);
            
            if ($user instanceof User) {
                $user->incrementLoginAttempts();
                
                // Lock account after 5 failed attempts
                if ($user->getLoginAttempts() >= 5) {
                    $lockedUntil = (new \DateTime())->add(new \DateInterval('PT15M'));
                    $user->setLockedUntil($lockedUntil);
                }
                
                $this->entityManager->persist($user);
                $this->entityManager->flush();
            }
        }

        return parent::onAuthenticationFailure($request, $exception);
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
