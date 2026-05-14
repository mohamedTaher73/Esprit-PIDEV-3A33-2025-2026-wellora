<?php

namespace App\Service;

use App\Entity\ProfessionalVerification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ProfessionalVerificationEmailService
{
    private MailerInterface $mailer;
    private EntityManagerInterface $entityManager;
    private string $noreplyAddress = 'zeidimohamedtaher@gmail.com';
    private string $appUrl = 'http://localhost:8000';

    public function __construct(
        MailerInterface $mailer,
        EntityManagerInterface $entityManager
    ) {
        $this->mailer = $mailer;
        $this->entityManager = $entityManager;
    }

    /**
     * Send approval email to professional
     */
    public function sendApprovalEmail(ProfessionalVerification $verification): bool
    {
        $user = $this->getUserFromVerification($verification);
        
        $emailAddress = null;
        $firstName = 'Cher(e)';
        $lastName = 'professionnel(le)';
        
        if ($user && $user->getEmail()) {
            $emailAddress = $user->getEmail();
            $firstName = $user->getFirstName() ?? $firstName;
            $lastName = $user->getLastName() ?? $lastName;
        } elseif ($verification->getProfessionalEmail()) {
            // Fallback to stored email in verification
            $emailAddress = $verification->getProfessionalEmail();
        }
        
        if (!$emailAddress) {
            error_log('ProfessionalVerificationEmailService: No email address found for verification ID: ' . $verification->getId());
            return false;
        }

        $professionalType = $user ? $this->getProfessionalTypeLabel($user->getRoles()) : 'Professionnel de santé';
        $specialty = $verification->getSpecialty() ?? 'N/A';

        $email = (new TemplatedEmail())
            ->from(new Address($this->noreplyAddress, 'WellCare Connect'))
            ->to($emailAddress)
            ->subject('✅ Vérification approuvée - WellCare Connect')
            ->htmlTemplate('email/professional_verification_approved.html.twig')
            ->context([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $emailAddress,
                'professionalType' => $professionalType,
                'specialty' => $specialty,
                'approvalDate' => new \DateTime(),
                'loginUrl' => $this->appUrl . '/login',
            ]);

        try {
            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            error_log('Failed to send approval email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send rejection email to professional
     */
    public function sendRejectionEmail(ProfessionalVerification $verification, string $reason): bool
    {
        $user = $this->getUserFromVerification($verification);
        
        $emailAddress = null;
        $firstName = 'Cher(e)';
        $lastName = 'professionnel(le)';
        
        if ($user && $user->getEmail()) {
            $emailAddress = $user->getEmail();
            $firstName = $user->getFirstName() ?? $firstName;
            $lastName = $user->getLastName() ?? $lastName;
        } elseif ($verification->getProfessionalEmail()) {
            // Fallback to stored email in verification
            $emailAddress = $verification->getProfessionalEmail();
        }
        
        if (!$emailAddress) {
            error_log('ProfessionalVerificationEmailService: No email address found for rejection, verification ID: ' . $verification->getId());
            return false;
        }

        $professionalType = $user ? $this->getProfessionalTypeLabel($user->getRoles()) : 'Professionnel de santé';

        $email = (new TemplatedEmail())
            ->from(new Address($this->noreplyAddress, 'WellCare Connect'))
            ->to($emailAddress)
            ->subject('❌ Vérification rejetée - WellCare Connect')
            ->htmlTemplate('email/professional_verification_rejected.html.twig')
            ->context([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'email' => $emailAddress,
                'professionalType' => $professionalType,
                'rejectionReason' => $reason,
                'loginUrl' => $this->appUrl . '/login',
            ]);

        try {
            $this->mailer->send($email);
            return true;
        } catch (\Exception $e) {
            error_log('Failed to send rejection email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user from verification using UUID
     */
    private function getUserFromVerification(ProfessionalVerification $verification): ?User
    {
        $uuid = $verification->getProfessionalUuid();
        
        if (!$uuid) {
            error_log('ProfessionalVerificationEmailService: No UUID found in verification ID: ' . $verification->getId());
            return null;
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['uuid' => $uuid]);
        
        if (!$user) {
            error_log('ProfessionalVerificationEmailService: User not found with UUID: ' . $uuid);
        }
        
        return $user;
    }

    /**
     * Get professional type label from roles
     */
    private function getProfessionalTypeLabel(array $roles): string
    {
        if (in_array('ROLE_MEDECIN', $roles)) {
            return 'Médecin';
        } elseif (in_array('ROLE_COACH', $roles)) {
            return 'Coach sportif';
        } elseif (in_array('ROLE_NUTRITIONIST', $roles)) {
            return 'Nutritionniste';
        } elseif (in_array('ROLE_ADMIN', $roles)) {
            return 'Administrateur';
        }
        
        return 'Professionnel de santé';
    }
}
