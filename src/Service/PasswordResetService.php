<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetService
{
    private const RESET_TOKEN_EXPIRY_HOURS = 2;
    private const MAX_RESET_REQUESTS_PER_HOUR = 3;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private UserPasswordHasherInterface $passwordHasher,
        private string $noreplyAddress = 'zeidimohamedtaher@gmail.com'
    ) {
        $this->appBaseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
    }

    /**
     * Generate a password reset token and send email
     * Returns array with success status and message
     */
    public function requestPasswordReset(string $email): array
    {
        // Check rate limiting (max 3 requests per hour per email)
        $recentRequests = $this->countRecentResetRequests($email);
        if ($recentRequests >= self::MAX_RESET_REQUESTS_PER_HOUR) {
            return [
                'success' => false,
                'message' => 'Vous avez atteint la limite de demandes de réinitialisation. Veuillez réessayer dans une heure.'
            ];
        }

        $user = $this->entityManager->getRepository(User::class)->findOneByEmail($email);
        
        if (!$user) {
            // Don't reveal if user exists - security best practice
            return [
                'success' => true,
                'message' => 'Si un compte existe avec cette adresse email, vous recevrez un lien de réinitialisation.'
            ];
        }

        // Check if user is active
        if (!$user->isIsActive()) {
            return [
                'success' => false,
                'message' => 'Ce compte a été désactivé. Veuillez contacter le support.'
            ];
        }

        // Generate reset token - use server timezone
        $resetToken = bin2hex(random_bytes(32));
        $resetTokenExpiresAt = new \DateTime('+' . self::RESET_TOKEN_EXPIRY_HOURS . ' hours');

        $user->setResetToken($resetToken);
        $user->setResetTokenExpiresAt($resetTokenExpiresAt);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Send email
        $emailError = null;
        try {
            $this->sendPasswordResetEmail($user);
        } catch (\Exception $e) {
            $emailError = $e->getMessage();
            error_log('Failed to send password reset email: ' . $emailError);
        }

        return [
            'success' => true,
            'message' => 'Si un compte existe avec cette adresse email, vous recevrez un lien de réinitialisation.',
            'debug' => $_ENV['APP_ENV'] === 'dev' ? $emailError : null
        ];
    }

    /**
     * Validate reset token
     * Returns user if valid, null otherwise
     */
    public function validateResetToken(string $token): ?User
    {
        if (empty($token)) {
            return null;
        }

        $user = $this->entityManager->getRepository(User::class)->findOneByResetToken($token);

        if (!$user) {
            return null;
        }

        return $user;
    }

    /**
     * Reset user password with new password
     * Returns array with success status and message
     */
    public function resetPassword(User $user, string $newPassword): array
    {
        // Validate password strength
        $validation = $this->validatePasswordStrength($newPassword);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message'],
                'errors' => $validation['errors']
            ];
        }

        // Hash the new password using Symfony's password hasher
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        
        // Clear reset token
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Send confirmation email
        try {
            $this->sendPasswordChangedEmail($user);
        } catch (\Exception $e) {
            error_log('Failed to send password changed confirmation email: ' . $e->getMessage());
        }

        return [
            'success' => true,
            'message' => 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.'
        ];
    }

    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail(User $user): void
    {
        $resetUrl = $this->appBaseUrl . '/reset-password?token=' . $user->getResetToken();
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->noreplyAddress, 'WellCare Connect'))
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe - WellCare Connect')
            ->htmlTemplate('email/password_reset.html.twig')
            ->context([
                'user' => $user,
                'resetUrl' => $resetUrl,
                'expiresAt' => $user->getResetTokenExpiresAt()->format('d/m/Y à H:i')
            ]);

        $this->mailer->send($email);
    }

    /**
     * Send password changed confirmation email
     */
    private function sendPasswordChangedEmail(User $user): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address($this->noreplyAddress, 'WellCare Connect'))
            ->to($user->getEmail())
            ->subject('Votre mot de passe a été modifié - WellCare Connect')
            ->htmlTemplate('email/password_changed.html.twig')
            ->context([
                'user' => $user,
                'appBaseUrl' => $this->appBaseUrl
            ]);

        $this->mailer->send($email);
    }

    /**
     * Count recent reset requests for rate limiting
     */
    private function countRecentResetRequests(string $email): int
    {
        $user = $this->entityManager->getRepository(User::class)->findOneByEmail($email);
        if (!$user) {
            return 0;
        }

        $oneHourAgo = new \DateTime('-1 hour');
        return $this->entityManager->createQueryBuilder()
            ->select('COUNT(u)')
            ->from(User::class, 'u')
            ->where('u.uuid = :userId')
            ->andWhere('u.resetTokenExpiresAt > :now')
            ->andWhere('u.resetTokenExpiresAt > :oneHourAgo')
            ->setParameter('userId', $user->getId())
            ->setParameter('now', new \DateTime())
            ->setParameter('oneHourAgo', $oneHourAgo)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Validate password strength
     */
    private function validatePasswordStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une majuscule.';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une minuscule.';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
        }

        return [
            'valid' => empty($errors),
            'message' => empty($errors) ? 'Mot de passe valide.' : 'Le mot de passe ne répond pas aux critères de sécurité.',
            'errors' => $errors
        ];
    }
}
