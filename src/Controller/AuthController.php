<?php

namespace App\Controller;

use App\Entity\Administrator;
use App\Entity\Coach;
use App\Entity\Medecin;
use App\Entity\Nutritionist;
use App\Entity\Patient;
use App\Entity\ProfessionalVerification;
use App\Entity\User;
use App\Form\LoginFormType;
use App\Form\RegistrationFormType;
use App\Security\Authenticator;
use App\Service\CaptchaService;
use App\Service\DiplomaVerificationService;
use App\Service\LoginValidationService;
use App\Service\PasswordResetService;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private LoginValidationService $loginValidationService,
        private PasswordResetService $passwordResetService,
        private EmailVerificationService $emailVerificationService,
        private TokenStorageInterface $tokenStorage,
        private CaptchaService $captchaService,
        private ?DiplomaVerificationService $diplomaVerificationService = null
    ) {}

    #[Route('/login', name: 'app_login')]
    public function login(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Get error from session if exists
        $error = null;
        $session = $request->getSession();
        if ($session->has('_security.last_error')) {
            $error = $session->get('_security.last_error');
        }

        // Get last username from session
        $lastEmail = $session->get(SecurityRequestAttributes::LAST_USERNAME, '');

        $form = $this->createForm(LoginFormType::class, null, [
            'action' => $this->generateUrl('app_login'),
        ]);

        return $this->render('auth/login.html.twig', [
            'loginForm' => $form->createView(),
            'error' => $error,
            'lastEmail' => $lastEmail,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/access-denied', name: 'app_access_denied')]
    public function accessDenied(Request $request): Response
    {
        // Get flash message if exists
        $errorMessage = 'Vous n\'avez pas l\'autorisation d\'accéder à cette page.';
        
        return $this->render('auth/access-denied.html.twig', [
            'error_message' => $errorMessage,
        ]);
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        
        $email = $request->request->get('email', '');
        
        if ($request->isMethod('POST')) {
            $result = $this->passwordResetService->requestPasswordReset($email);
            
            if ($result['success']) {
                $this->addFlash('success', $result['message']);
            } else {
                $this->addFlash('error', $result['message']);
            }
            
            return $this->redirectToRoute('app_forgot_password');
        }
        
        return $this->render('auth/forgot-password.html.twig', [
            'email' => $email,
        ]);
    }

    #[Route('/reset-password', name: 'app_reset_password')]
    public function resetPassword(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        
        // Get token from URL query or POST data
        $token = $request->query->get('token', $request->request->get('token', ''));
        
        // Check if token is valid
        $user = $this->passwordResetService->validateResetToken($token);
        
        if (!$user) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou a expiré. Veuillez refaire une demande.');
            return $this->redirectToRoute('app_forgot_password');
        }
        
        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password', '');
            $confirmPassword = $request->request->get('confirm_password', '');
            
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->render('auth/reset-password.html.twig', [
                    'token' => $token,
                ]);
            }
            
            $result = $this->passwordResetService->resetPassword($user, $newPassword);
            
            if ($result['success']) {
                $this->addFlash('success', $result['message']);
                return $this->redirectToRoute('app_login');
            } else {
                $this->addFlash('error', $result['message']);
            }
        }
        
        return $this->render('auth/reset-password.html.twig', [
            'token' => $token,
        ]);
    }

    #[Route('/api/forgot-password', name: 'app_api_forgot_password', methods: ['POST'])]
    public function apiForgotPassword(Request $request): JsonResponse
    {
        $email = $request->request->get('email', '');
        
        if (empty($email)) {
            return $this->json([
                'success' => false,
                'message' => 'Veuillez entrer votre adresse email.'
            ]);
        }
        
        $result = $this->passwordResetService->requestPasswordReset($email);
        
        return $this->json($result);
    }
    
    #[Route('/api/login/validate', name: 'app_login_validate', methods: ['POST'])]
    public function validateLogin(Request $request): JsonResponse
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        
        $result = $this->loginValidationService->validateCredentials($email, $password);
        
        return $this->json($result);
    }
    
    #[Route('/api/login/scenarios', name: 'app_login_scenarios', methods: ['GET'])]
    public function getLoginScenarios(): JsonResponse
    {
        return $this->json([
            'scenarios' => $this->loginValidationService->getLoginScenarios()
        ]);
    }

    #[Route('/api/check-email', name: 'app_check_email', methods: ['POST'])]
    public function checkEmailAvailability(Request $request): JsonResponse
    {
        $email = $request->request->get('email', '');
        
        if (empty($email)) {
            return $this->json([
                'available' => false,
                'message' => 'Veuillez entrer une adresse email.'
            ]);
        }
        
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        
        return $this->json([
            'available' => $user === null,
            'message' => $user === null ? 'Cette adresse email est disponible.' : 'Cette adresse email est déjà utilisée.'
        ]);
    }

    #[Route('/api/check-phone', name: 'app_check_phone', methods: ['POST'])]
    public function checkPhoneAvailability(Request $request): JsonResponse
    {
        $phone = $request->request->get('phone', '');
        
        if (empty($phone)) {
            return $this->json([
                'available' => false,
                'message' => 'Veuillez entrer un numéro de téléphone.'
            ]);
        }
        
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['phone' => $phone]);
        
        return $this->json([
            'available' => $user === null,
            'message' => $user === null ? 'Ce numéro de téléphone est disponible.' : 'Ce numéro de téléphone est déjà utilisé.'
        ]);
    }

    #[Route('/register/patient', name: 'app_register_patient')]
    public function registerPatient(Request $request, MailerInterface $mailer): Response
    {
        return $this->register($request, 'patient', $mailer);
    }

    #[Route('/register/medecin', name: 'app_register_medecin')]
    public function registerMedecin(Request $request, MailerInterface $mailer): Response
    {
        return $this->register($request, 'medecin', $mailer);
    }

    #[Route('/register/coach', name: 'app_register_coach')]
    public function registerCoach(Request $request, MailerInterface $mailer): Response
    {
        return $this->register($request, 'coach', $mailer);
    }

    #[Route('/register/nutritionist', name: 'app_register_nutritionist')]
    public function registerNutritionist(Request $request, MailerInterface $mailer): Response
    {
        return $this->register($request, 'nutritionist', $mailer);
    }

    /**
     * @deprecated Use specific routes (app_register_medecin, app_register_coach, app_register_nutritionist) instead
     */
    #[Route('/register/professional', name: 'app_register_professional')]
    public function registerProfessional(Request $request, MailerInterface $mailer): Response
    {
        return $this->register($request, 'professional', $mailer);
    }
    
    /**
     * Unified registration method for both patient and professional users.
     * Handles form display, validation, user creation, and email verification.
     */
    private function register(Request $request, string $type, MailerInterface $mailer): Response
    {
        // DEBUG: Log request info
        error_log("[DEBUG] register() called - type: " . $type);
        error_log("[DEBUG] Request method: " . $request->getMethod());
        error_log("[DEBUG] Request URI: " . $request->getUri());
        
        // Redirect to home if already logged in
        if ($this->getUser()) {
            error_log("[DEBUG] User already logged in, redirecting to home");
            return $this->redirectToRoute('app_home');
        }

        // Determine user class based on type parameter
        $userClass = match ($type) {
            'patient' => Patient::class,
            'medecin' => Medecin::class,
            'coach' => Coach::class,
            'nutritionist' => Nutritionist::class,
            default => Patient::class,
        };
        
        // Create user based on type
        $user = new $userClass();
        
        $form = $this->createForm(RegistrationFormType::class, $user, [
            'type' => $type
        ]);
        
        $form->handleRequest($request);
        
        error_log("[DEBUG] Form isSubmitted: " . ($form->isSubmitted() ? 'true' : 'false'));
        
        // Fallback: If form is not detected as submitted but we have POST data, force submission
        if (!$form->isSubmitted() && $request->isMethod('POST')) {
            error_log("[DEBUG] Form not detected as submitted but POST data present - forcing submission");
            
            // DEBUG: Log all POST data keys
            $postKeys = array_keys($request->request->all());
            error_log("[DEBUG] POST data keys: " . implode(', ', $postKeys));
            
            // Only submit fields that actually belong to the Symfony form.
            // This avoids "extra fields" errors for custom top-level inputs
            // like captcha_code, _submit, _captcha_validated, etc.
            $postData = $request->request->all();
            $allowedFields = array_keys($form->all());
            $formData = [];
            foreach ($allowedFields as $fieldName) {
                if (array_key_exists($fieldName, $postData)) {
                    $formData[$fieldName] = $postData[$fieldName];
                }
            }
            
            // Submit form WITHOUT CSRF validation (false parameter)
            $form->submit($formData, false);
        }
        
        if ($form->isSubmitted()) {
            error_log("[DEBUG] Form isValid: " . ($form->isValid() ? 'true' : 'false'));
            
            // ========== CAPTCHA VALIDATION ==========
            $captchaCode = $request->request->get('captcha_code');
            error_log("[DEBUG] Captcha code submitted: " . ($captchaCode ? 'yes' : 'no'));
            
            if (empty($captchaCode)) {
                $this->addFlash('error', 'Veuillez entrer le code de vérification.');
                error_log("[DEBUG] Captcha validation failed - empty code");
                $template = $type === 'patient' ? 'auth/register-patient.html.twig' : 'auth/register-professional.html.twig';
                return $this->render($template, [
                    'registrationForm' => $form->createView(),
                    'type' => $type
                ]);
            }
            
            if (!$this->captchaService->validate($captchaCode)) {
                $this->addFlash('error', 'Le code de vérification est incorrect. Veuillez réessayer.');
                error_log("[DEBUG] Captcha validation failed - invalid code");
                $template = $type === 'patient' ? 'auth/register-patient.html.twig' : 'auth/register-professional.html.twig';
                return $this->render($template, [
                    'registrationForm' => $form->createView(),
                    'type' => $type
                ]);
            }
            error_log("[DEBUG] Captcha validation passed");
            // ========== END CAPTCHA VALIDATION ==========
            
            // DEBUG: Log form data
            error_log("[DEBUG] Form submitted - email: " . $user->getEmail());
            error_log("[DEBUG] Form submitted - firstName: " . $user->getFirstName());
            error_log("[DEBUG] Form submitted - lastName: " . $user->getLastName());
            
            // Handle birthdate from hidden field (format: YYYY-MM-DD)
            $birthdateValue = $request->request->get('birthdate');
            error_log("[DEBUG] Raw birthdate value from form: " . ($birthdateValue ?: 'NOT SET'));
            if (!empty($birthdateValue)) {
                try {
                    $birthdate = \DateTime::createFromFormat('Y-m-d', $birthdateValue);
                    if ($birthdate !== false) {
                        $user->setBirthdate($birthdate);
                        error_log("[DEBUG] Birthdate parsed successfully: " . $birthdate->format('Y-m-d'));
                    }
                } catch (\Exception $e) {
                    error_log("[DEBUG] Exception parsing birthdate: " . $e->getMessage());
                }
            }
            
            // Validate agreeTerms checkbox
            $agreeTerms = $form->get('agreeTerms')->getData();
            error_log("[DEBUG] agreeTerms value: " . ($agreeTerms ? 'true' : 'false'));
            if (!$agreeTerms) {
                $this->addFlash('error', 'Vous devez accepter les conditions générales d\'utilisation.');
                error_log("[DEBUG] agreeTerms validation failed");
                $template = $type === 'patient' ? 'auth/register-patient.html.twig' : 'auth/register-professional.html.twig';
                return $this->render($template, [
                    'registrationForm' => $form->createView(),
                    'type' => $type,
                ]);
            }
            
            // Check if form has validation errors
            if (!$form->isValid()) {
                $errors = [];
                foreach ($form->getErrors(true, true) as $error) {
                    $errors[] = $error->getMessage();
                }
                error_log("[DEBUG] Form validation errors: " . implode(', ', $errors));
                $this->addFlash('error', 'Erreurs de validation: ' . implode(', ', $errors));
                $template = $type === 'patient' ? 'auth/register-patient.html.twig' : 'auth/register-professional.html.twig';
                return $this->render($template, [
                    'registrationForm' => $form->createView(),
                    'type' => $type,
                ]);
            }
            
            // Get password from RepeatedType field
            $plainPassword = $form->get('plainPassword')->get('first')->getData();
            $confirmPassword = $form->get('plainPassword')->get('second')->getData();
            error_log("[DEBUG] Password extracted - plainPassword: " . ($plainPassword ? 'present' : 'null'));
            error_log("[DEBUG] Password extracted - confirmPassword: " . ($confirmPassword ? 'present' : 'null'));
            
            // Validate password match
            if ($plainPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                error_log("[DEBUG] Passwords don't match");
                $template = $type === 'patient' ? 'auth/register-patient.html.twig' : 'auth/register-professional.html.twig';
                return $this->render($template, [
                    'registrationForm' => $form->createView(),
                    'type' => $type,
                ]);
            }
            
            // Validate password strength (server-side)
            $passwordValidation = $this->loginValidationService->validatePasswordStrength($plainPassword);
            if (!$passwordValidation['valid']) {
                $errorMessages = implode('. ', $passwordValidation['messages']);
                $this->addFlash('error', 'Mot de passe trop faible: ' . $errorMessages);
                error_log("[DEBUG] Password strength validation failed: " . $errorMessages);
                $template = $type === 'patient' ? 'auth/register-patient.html.twig' : 'auth/register-professional.html.twig';
                return $this->render($template, [
                    'registrationForm' => $form->createView(),
                    'type' => $type,
                ]);
            }
            
            // Validate email uniqueness
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $user->getEmail()]);
            if ($existingUser) {
                $this->addFlash('error', 'Cette adresse email est déjà utilisée.');
                error_log("[DEBUG] Email already exists");
                $template = $type === 'patient' ? 'auth/register-patient.html.twig' : 'auth/register-professional.html.twig';
                return $this->render($template, [
                    'registrationForm' => $form->createView(),
                    'type' => $type,
                ]);
            }
            
            // Validate phone uniqueness (if phone is provided)
            $phone = $user->getPhone();
            if (!empty($phone)) {
                $existingPhoneUser = $this->entityManager->getRepository(User::class)->findOneBy(['phone' => $phone]);
                if ($existingPhoneUser) {
                    $this->addFlash('error', 'Ce numéro de téléphone est déjà utilisé par un autre compte.');
                    error_log("[DEBUG] Phone already exists: " . $phone);
                    $template = $type === 'patient' ? 'auth/register-patient.html.twig' : 'auth/register-professional.html.twig';
                    return $this->render($template, [
                        'registrationForm' => $form->createView(),
                        'type' => $type,
                    ]);
                }
            }
            
            // Validate license number uniqueness (for professionals)
            $licenseNumber = $request->request->get('license_number');
            if (!empty($licenseNumber) && in_array($type, ['medecin', 'coach', 'nutritionist', 'professional'])) {
                $existingLicenseUser = $this->entityManager->getRepository(User::class)->findOneBy(['licenseNumber' => $licenseNumber]);
                if ($existingLicenseUser) {
                    $this->addFlash('error', 'Ce numéro de licence est déjà utilisé par un autre professionnel.');
                    error_log("[DEBUG] License number already exists: " . $licenseNumber);
                    $template = $type === 'patient' ? 'auth/register-patient.html.twig' : 'auth/register-professional.html.twig';
                    return $this->render($template, [
                        'registrationForm' => $form->createView(),
                        'type' => $type,
                    ]);
                }
            }
            
            // Hash password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);
            error_log("[DEBUG] Password hashed successfully");
            
            try {
                // Set role discriminator for STI
                $role = match ($userClass) {
                    Patient::class => 'ROLE_PATIENT',
                    Medecin::class => 'ROLE_MEDECIN',
                    Coach::class => 'ROLE_COACH',
                    Nutritionist::class => 'ROLE_NUTRITIONIST',
                    default => 'ROLE_PATIENT',
                };
                
                // Use reflection to set the discriminator value
                $reflectionClass = new \ReflectionClass($user);
                $parentClass = $reflectionClass->getParentClass();
                if ($parentClass && $parentClass->hasProperty('role')) {
                    $roleProperty = $parentClass->getProperty('role');
                    $roleProperty->setAccessible(true);
                    $roleProperty->setValue($user, $role);
                }
                
                // Set default values
                $user->setCreatedAt(new \DateTime());
                $user->setLoginAttempts(0);
                $user->setIsEmailVerified(false);
                
                // Handle professional-specific fields
                if ($user instanceof Medecin) {
                    // Get specialization from request
                    $specialization = $request->request->get('specialization');
                    if ($specialization) {
                        $user->setSpecialite($specialization);
                        error_log("[DEBUG] Setting Medecin specialite: " . $specialization);
                    }
                    
                    // Get license number from request
                    $licenseNumber = $request->request->get('license_number');
                    if ($licenseNumber) {
                        $user->setLicenseNumber($licenseNumber);
                        error_log("[DEBUG] Setting Medecin licenseNumber: " . $licenseNumber);
                    }
                    
                    // Get years of experience from request
                    $yearsOfExperience = $request->request->get('years_of_experience');
                    if ($yearsOfExperience !== null && $yearsOfExperience !== '') {
                        $user->setYearsOfExperience((int) $yearsOfExperience);
                        error_log("[DEBUG] Setting Medecin yearsOfExperience: " . $yearsOfExperience);
                    }
                    
                    // Handle diploma file upload
                    $diplomaFile = $request->files->get('diploma');
                    if ($diplomaFile instanceof UploadedFile) {
                        $diplomaFilename = $this->uploadFile($diplomaFile, 'diplomas');
                        $user->setDiplomaUrl('/uploads/diplomas/' . $diplomaFilename);
                        error_log("[DEBUG] Diploma uploaded: " . $diplomaFilename);
                    }
                } elseif ($user instanceof Coach) {
                    // Get license number from request
                    $licenseNumber = $request->request->get('license_number');
                    if ($licenseNumber) {
                        $user->setLicenseNumber($licenseNumber);
                        error_log("[DEBUG] Setting Coach licenseNumber: " . $licenseNumber);
                    }
                    
                    // Get specialization from request
                    $specialization = $request->request->get('specialization');
                    if ($specialization) {
                        $user->setSpecialite($specialization);
                        error_log("[DEBUG] Setting Coach specialite: " . $specialization);
                    }
                    
                    // Get years of experience from request
                    $yearsOfExperience = $request->request->get('years_of_experience');
                    if ($yearsOfExperience !== null && $yearsOfExperience !== '') {
                        $user->setYearsOfExperience((int) $yearsOfExperience);
                        error_log("[DEBUG] Setting Coach experience: " . $yearsOfExperience);
                    }
                    
                    // Handle diploma file upload
                    $diplomaFile = $request->files->get('diploma');
                    if ($diplomaFile instanceof UploadedFile) {
                        $diplomaFilename = $this->uploadFile($diplomaFile, 'diplomas');
                        $user->setDiplomaUrl('/uploads/diplomas/' . $diplomaFilename);
                        error_log("[DEBUG] Diploma uploaded: " . $diplomaFilename);
                    }
                } elseif ($user instanceof Nutritionist) {
                    // Get license number from request
                    $licenseNumber = $request->request->get('license_number');
                    if ($licenseNumber) {
                        $user->setLicenseNumber($licenseNumber);
                        error_log("[DEBUG] Setting Nutritionist licenseNumber: " . $licenseNumber);
                    }
                    
                    // Get specialization from request
                    $specialization = $request->request->get('specialization');
                    if ($specialization) {
                        $user->setSpecialite($specialization);
                        error_log("[DEBUG] Setting Nutritionist specialite: " . $specialization);
                    }
                    
                    // Get years of experience from request
                    $yearsOfExperience = $request->request->get('years_of_experience');
                    if ($yearsOfExperience !== null && $yearsOfExperience !== '') {
                        $user->setYearsOfExperience((int) $yearsOfExperience);
                        error_log("[DEBUG] Setting Nutritionist experience: " . $yearsOfExperience);
                    }
                    
                    // Handle diploma file upload
                    $diplomaFile = $request->files->get('diploma');
                    if ($diplomaFile instanceof UploadedFile) {
                        $diplomaFilename = $this->uploadFile($diplomaFile, 'diplomas');
                        $user->setDiplomaUrl('/uploads/diplomas/' . $diplomaFilename);
                        error_log("[DEBUG] Diploma uploaded: " . $diplomaFilename);
                    }
                }
                
                // Persist user
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                error_log("[DEBUG] User saved to database - id: " . $user->getId());
                
                // Create professional verification record if applicable
                if ($this->diplomaVerificationService && in_array($type, ['medecin', 'coach', 'nutritionist', 'professional'])) {
                    try {
                        $diplomaPath = null;
                        
                        // Get diploma path from user entity
                        if (method_exists($user, 'getDiplomaUrl') && $user->getDiplomaUrl()) {
                            $diplomaPath = $user->getDiplomaUrl();
                        }
                        
                        // Get license number and specialty
                        $licenseNumber = null;
                        $specialty = null;
                        
                        if (method_exists($user, 'getLicenseNumber')) {
                            $licenseNumber = $user->getLicenseNumber();
                        }
                        if (method_exists($user, 'getSpecialite')) {
                            $specialty = $user->getSpecialite();
                        }
                        
                        // Create verification record
                        $verification = new ProfessionalVerification();
                        $verification->setProfessionalUuid($user->getUuid());
                        $verification->setProfessionalEmail($user->getEmail());
                        $verification->setLicenseNumber($licenseNumber);
                        $verification->setSpecialty($specialty);
                        $verification->setDiplomaPath($diplomaPath);
                        $verification->setStatus(ProfessionalVerification::STATUS_PENDING);
                        $verification->setCreatedAt(new \DateTime());
                        
                        $this->entityManager->persist($verification);
                        $this->entityManager->flush();
                        
                        error_log("[DEBUG] Professional verification record created - id: " . $verification->getId());
                        
                        // Process verification automatically if diploma is available
                        if ($diplomaPath) {
                            try {
                                $verification = $this->diplomaVerificationService->processVerification($verification);
                                $this->entityManager->flush();
                                error_log("[DEBUG] Verification processed - score: " . $verification->getConfidenceScore());
                            } catch (\Exception $e) {
                                error_log("[DEBUG] Verification processing error: " . $e->getMessage());
                            }
                        }
                    } catch (\Exception $e) {
                        error_log("[DEBUG] Error creating verification record: " . $e->getMessage());
                    }
                }
                
                // Send verification email
                try {
                    $this->emailVerificationService->sendVerificationEmail($user);
                    error_log("[DEBUG] Verification email sent");
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de la vérification email: ' . $e->getMessage());
                    error_log("[DEBUG] Email send error: " . $e->getMessage());
                }
                
                $successMessage = match ($userClass) {
                    Patient::class => 'Votre compte a été créé avec succès ! Veuillez vérifier votre email pour activer votre compte.',
                    Medecin::class => 'Votre compte médecin a été créé avec succès ! Veuillez vérifier votre email. Un administrateur vérifiera vos documents sous 24-48 heures.',
                    Coach::class => 'Votre compte coach a été créé avec succès ! Veuillez vérifier votre email. Un administrateur vérifiera vos documents sous 24-48 heures.',
                    Nutritionist::class => 'Votre compte nutritionniste a été créé avec succès ! Veuillez vérifier votre email. Un administrateur vérifiera vos documents sous 24-48 heures.',
                    default => 'Votre compte a été créé avec succès !',
                };
                
                $this->addFlash('success', $successMessage);
                error_log("[DEBUG] Success, redirecting to login");
                return $this->redirectToRoute('app_login');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création du compte: ' . $e->getMessage());
                error_log("[DEBUG] Error saving user: " . $e->getMessage());
                error_log("[DEBUG] Error trace: " . $e->getTraceAsString());
            }
        }
        
        // Use appropriate template based on type
        $template = $type === 'patient' ? 'auth/register-patient.html.twig' : 'auth/register-professional.html.twig';
        
        return $this->render($template, [
            'registrationForm' => $form->createView(),
            'type' => $type,
        ]);
    }

    #[Route('/verify-email', name: 'app_verify_email')]
    public function verifyEmail(Request $request): Response
    {
        $token = $request->query->get('token', '');
        $prefillEmail = $request->query->get('email', '');
        
        // If token is provided, verify email
        if (!empty($token)) {
            $user = $this->emailVerificationService->verifyEmail($token);
            
            if ($user) {
                // Set success flash message with user type
                if ($user instanceof Medecin || $user instanceof Coach || $user instanceof Nutritionist) {
                    $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès ! Un administrateur va vérifier vos documents sous 24-48 heures.');
                } else {
                    $this->addFlash('success', 'Votre adresse email a été vérifiée avec succès ! Bienvenue sur WellCare Connect.');
                }
                
                // Redirect to verification success page (NOT auto-login)
                return $this->redirectToRoute('app_verify_email_success');
            } else {
                $this->addFlash('error', 'Le lien de vérification est invalide ou a expiré. Veuillez refaire une demande.');
                return $this->redirectToRoute('app_verify_email');
            }
        }
        
        // If user is logged in and not verified, show pending message
        $user = $this->getUser();
        
        return $this->render('auth/verify-email.html.twig', [
            'user' => $user,
            'hasToken' => !empty($token),
            'prefillEmail' => $prefillEmail,
        ]);
    }
    
    #[Route('/verify-email/success', name: 'app_verify_email_success')]
    public function verifyEmailSuccess(Request $request): Response
    {
        // Get user from session (if logged in) or from token verification
        $user = $this->getUser();
        
        // Determine user type for appropriate message
        $userType = 'patient';
        $userRole = null;
        
        if ($user) {
            if ($user instanceof Medecin) {
                $userType = 'professional';
                $userRole = 'médecin';
            } elseif ($user instanceof Coach) {
                $userType = 'professional';
                $userRole = 'coach';
            } elseif ($user instanceof Nutritionist) {
                $userType = 'professional';
                $userRole = 'nutritionniste';
            }
        }
        
        return $this->render('auth/verify-email-success.html.twig', [
            'userType' => $userType,
            'userRole' => $userRole,
        ]);
    }

    #[Route('/api/resend-verification-email', name: 'app_resend_verification_email', methods: ['POST'])]
    public function resendVerificationEmail(): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Vous devez être connecté pour renvoyer l\'email de vérification.'
            ]);
        }
        
        try {
            $this->emailVerificationService->resendVerificationEmail($user);
            return $this->json([
                'success' => true,
                'message' => 'Un nouvel email de vérification vous a été envoyé.'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi de l\'email: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/api/resend-verification-email-public', name: 'app_resend_verification_email_public', methods: ['POST'])]
    public function resendVerificationEmailPublic(Request $request): JsonResponse
    {
        $email = trim((string) $request->request->get('email', ''));

        if ($email === '') {
            return $this->json([
                'success' => false,
                'message' => 'Veuillez entrer votre adresse email.'
            ]);
        }

        try {
            $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user instanceof User && !$user->isEmailVerified()) {
                $this->emailVerificationService->resendVerificationEmail($user);
            }
        } catch (\Throwable $e) {
            error_log('[DEBUG] Public resend verification failed: ' . $e->getMessage());
        }

        return $this->json([
            'success' => true,
            'message' => 'Si un compte non vérifié existe avec cette adresse, un email de vérification a été envoyé.'
        ]);
    }
    
    #[Route('/api/validate-password', name: 'app_validate_password', methods: ['POST'])]
    public function validatePassword(Request $request): JsonResponse
    {
        $password = $request->request->get('password', '');
        
        if (empty($password)) {
            return $this->json([
                'valid' => false,
                'strength' => 0,
                'level' => 'weak',
                'errors' => ['Le mot de passe est requis']
            ]);
        }
        
        $result = $this->loginValidationService->validatePasswordStrength($password);
        
        return $this->json([
            'valid' => $result['valid'],
            'strength' => $result['strength'],
            'level' => $result['level'],
            'errors' => $result['messages'],
            'requirements' => $result['requirements']
        ]);
    }
    
    #[Route('/api/check-license-number', name: 'app_check_license_number', methods: ['POST'])]
    public function checkLicenseNumber(Request $request): JsonResponse
    {
        $licenseNumber = $request->request->get('license_number', '');
        $excludeEmail = $request->request->get('exclude_email', '');
        
        if (empty($licenseNumber)) {
            return $this->json([
                'available' => true,
                'message' => ''
            ]);
        }
        
        // Find user with this license number
        $user = $this->entityManager->getRepository(User::class)->findOneByLicenseNumber($licenseNumber);
        
        if ($user) {
            // Check if it's the same user (for editing)
            if (!empty($excludeEmail) && $user->getEmail() === $excludeEmail) {
                return $this->json([
                    'available' => true,
                    'message' => ''
                ]);
            }
            
            return $this->json([
                'available' => false,
                'message' => 'Ce numéro de licence est déjà utilisé par un autre professionnel.'
            ]);
        }
        
        return $this->json([
            'available' => true,
            'message' => ''
        ]);
    }
    
    #[Route('/terms', name: 'app_terms')]
    public function terms(): Response
    {
        return $this->render('auth/terms-modal.html.twig');
    }
    
    /**
     * Redirect user to appropriate dashboard based on their role
     */
    private function getRoleBasedRedirect(User $user): RedirectResponse
    {
        $roles = $user->getRoles();
        
        if (in_array('ROLE_ADMIN', $roles)) {
            // Admin dashboard route doesn't exist yet, redirect to admin trail analytics
            return $this->redirectToRoute('admin_trail_analytics');
        }
        
        if (in_array('ROLE_MEDECIN', $roles)) {
            // Doctor dashboard route doesn't exist yet, redirect to patient list
            return $this->redirectToRoute('doctor_main_patient_list');
        }
        
        if (in_array('ROLE_COACH', $roles)) {
            return $this->redirectToRoute('coach_clients');
        }
        
        if (in_array('ROLE_NUTRITIONIST', $roles)) {
            return $this->redirectToRoute('nutritionniste_dashboard');
        }
        
        // Default to patient dashboard for ROLE_PATIENT
        return $this->redirectToRoute('appointment_patient_dashboard');
    }

    /**
     * Upload a file to the public/uploads directory
     *
     * @param UploadedFile $file The file to upload
     * @param string $subdirectory The subdirectory within uploads (e.g., 'diplomas', 'avatars')
     * @return string The filename of the uploaded file
     */
    private function uploadFile(UploadedFile $file, string $subdirectory): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // Sanitize filename - replace non-ASCII characters
        $safeFilename = preg_replace('/[^A-Za-z0-9_-]/', '_', $originalFilename);
        $safeFilename = preg_replace('/_+/', '_', $safeFilename);
        $safeFilename = trim($safeFilename, '_');
        
        // Get file extension - try guessExtension first, then fallback to client original extension
        try {
            $fileExtension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        } catch (\Exception $e) {
            // If guessExtension fails, use the original extension
            $fileExtension = $file->getClientOriginalExtension() ?: 'bin';
        }
        
        $filename = sprintf('%s_%s.%s', $safeFilename, uniqid('', true), $fileExtension);
        
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . $subdirectory;
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $file->move($uploadDir, $filename);
        
        return $filename;
    }
}
