<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailVerificationService
{
    private const VERIFICATION_TOKEN_EXPIRY_HOURS = 24;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private string $noreplyAddress = 'zeidimohamedtaher@gmail.com'
    ) {
        $this->appBaseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
    }

    /**
     * Generate verification token and send email
     * @return bool Returns true if email was sent successfully
     */
    public function sendVerificationEmail(User $user): bool
    {
        // Generate verification token
        $verificationToken = bin2hex(random_bytes(32));
        $verificationExpiresAt = new \DateTime('+' . self::VERIFICATION_TOKEN_EXPIRY_HOURS . ' hours');

        // Set token on user entity
        $user->setEmailVerificationToken($verificationToken);
        $user->setEmailVerificationExpiresAt($verificationExpiresAt);
        
        // Persist and flush
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        // Refresh to ensure data is saved
        $this->entityManager->refresh($user);

        // Build verification URL
        $verificationUrl = $this->appBaseUrl . '/verify-email?token=' . $verificationToken;
        
        // Send verification email
        $email = (new TemplatedEmail())
            ->from(new Address($this->noreplyAddress, 'WellCare Connect'))
            ->to($user->getEmail())
            ->subject('Vérifiez votre adresse email - WellCare Connect')
            ->htmlTemplate('email/email_verification.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
                'expiresAt' => $verificationExpiresAt->format('d/m/Y à H:i')
            ]);

        $this->mailer->send($email);
        
        return true;
    }

    /**
     * Verify email with token
     * Returns the User object if successful, null otherwise
     */
    public function verifyEmail(string $token): ?User
    {
        if (empty($token)) {
            return null;
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'emailVerificationToken' => $token
        ]);

        if (!$user) {
            return null;
        }

        // Check if token is expired
        if ($user->getEmailVerificationExpiresAt() < new \DateTime()) {
            return null;
        }

        // Mark email as verified
        $user->setIsEmailVerified(true);
        $user->setEmailVerificationToken(null);
        $user->setEmailVerificationExpiresAt(null);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    /**
     * Resend verification email
     */
    public function resendVerificationEmail(User $user): void
    {
        // Generate new token
        $verificationToken = bin2hex(random_bytes(32));
        $verificationExpiresAt = new \DateTime('+' . self::VERIFICATION_TOKEN_EXPIRY_HOURS . ' hours');

        // Use Doctrine Query Builder for the update
        $this->entityManager->createQueryBuilder()
            ->update('App\Entity\User', 'u')
            ->set('u.emailVerificationToken', ':token')
            ->set('u.emailVerificationExpiresAt', ':expires')
            ->where('u.email = :email')
            ->setParameter('token', $verificationToken)
            ->setParameter('expires', $verificationExpiresAt)
            ->setParameter('email', $user->getEmail())
            ->getQuery()
            ->execute();
        
        $this->entityManager->clear();

        // Send verification email
        $verificationUrl = $this->appBaseUrl . '/verify-email?token=' . $verificationToken;
        
        $email = (new TemplatedEmail())
            ->from(new Address($this->noreplyAddress, 'WellCare Connect'))
            ->to($user->getEmail())
            ->subject('Vérifiez votre adresse email - WellCare Connect')
            ->htmlTemplate('email/email_verification.html.twig')
            ->context([
                'user' => $user,
                'verificationUrl' => $verificationUrl,
                'expiresAt' => $verificationExpiresAt->format('d/m/Y à H:i')
            ]);

        $this->mailer->send($email);
    }
}
