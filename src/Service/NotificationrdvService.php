<?php
// src/Service/NotificationrdvService.php

namespace App\Service;

use App\Entity\Notificationrdv;
use App\Entity\Consultation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class NotificationrdvService
{
    public function __construct(
        private MailerInterface $mailer,
        private EntityManagerInterface $em,
        private Environment $twig,
        private LoggerInterface $logger,
        private string $smsFrom = '+21612345678' // Numéro expéditeur
    ) {}

    /**
     * Envoie la confirmation immédiate de rendez-vous
     */
    public function envoyerConfirmation(Consultation $consultation): void
    {
        $patient = $consultation->getPatient();
        $medecin = $consultation->getMedecin();
        $date = $consultation->getDateConsultation();
        $time = $consultation->getTimeConsultation();
        
        // Formatage de la date en français
        $dateFormatee = $date ? $date->format('d/m/Y') : 'N/A';
        $heureFormatee = $time ? $time->format('H:i') : 'N/A';
        
        // Construction du message commun
        $message = sprintf(
            "WellCare: Votre rendez-vous avec Dr %s %s est confirmé pour le %s à %s.\nMotif: %s\nMerci d'arriver 10min avant.",
            $medecin ? $medecin->getFirstName() : 'N/A',
            $medecin ? $medecin->getLastName() : 'N/A',
            $dateFormatee,
            $heureFormatee,
            $consultation->getReasonForVisit() ?? 'Consultation'
        );
        
        // Envoyer par SMS si le patient a un téléphone
        if ($patient && $patient->getPhone()) {
            $this->envoyerSMS($patient->getPhone(), $message, $consultation, 'confirmation');
        }
        
        // Envoyer par email si le patient a un email
        if ($patient && $patient->getEmail()) {
            $this->envoyerEmailConfirmation($patient->getEmail(), $consultation, $message);
        }
    }

    /**
     * Envoie le rappel J-1
     */
    public function envoyerRappel(Consultation $consultation): void
    {
        $patient = $consultation->getPatient();
        $medecin = $consultation->getMedecin();
        $time = $consultation->getTimeConsultation();
        
        $heureFormatee = $time ? $time->format('H:i') : 'N/A';
        
        $message = sprintf(
            "Rappel WellCare: Votre rendez-vous avec Dr %s %s est DEMAIN à %s.",
            $medecin ? $medecin->getFirstName() : 'N/A',
            $medecin ? $medecin->getLastName() : 'N/A',
            $heureFormatee
        );
        
        // Envoyer par SMS (priorité)
        if ($patient && $patient->getPhone()) {
            $this->envoyerSMS($patient->getPhone(), $message, $consultation, 'rappel');
        }
        
        // Optionnel : envoyer aussi par email
        if ($patient && $patient->getEmail()) {
            $this->envoyerEmailRappel($patient->getEmail(), $consultation, $message);
        }
    }

    /**
     * Envoi d'un SMS via Symfony Notifier (non configuré)
     */
    private function envoyerSMS(string $to, string $message, Consultation $consultation, string $type): void
    {
        // SMS non configuré - journaliser seulement
        $this->logger->info('SMS non envoyé (service non configuré)', [
            'consultation_id' => $consultation->getId(),
            'type' => $type,
            'phone' => $to
        ]);
    }

    /**
     * Envoi d'un email de confirmation
     */
    private function envoyerEmailConfirmation(string $to, Consultation $consultation, string $texte): void
    {
        try {
            // Générer le contenu HTML à partir d'un template Twig
            $htmlContent = $this->twig->render('emails/confirmation_rdv.html.twig', [
                'consultation' => $consultation
            ]);
            
            $email = (new Email())
                ->from('no-reply@wellcare.tn')
                ->to($to)
                ->subject('Confirmation de votre rendez-vous WellCare')
                ->text($texte)
                ->html($htmlContent);
            
            $this->mailer->send($email);
            
            $this->logNotification($consultation, 'email', $to, $texte, 'envoye', 'confirmation');
            
        } catch (\Exception $e) {
            $this->logNotification($consultation, 'email', $to, $texte, 'echec', 'confirmation', $e->getMessage());
        }
    }

    /**
     * Envoi d'un email de rappel
     */
    private function envoyerEmailRappel(string $to, Consultation $consultation, string $texte): void
    {
        try {
            $htmlContent = $this->twig->render('emails/rappel_rdv.html.twig', [
                'consultation' => $consultation
            ]);
            
            $email = (new Email())
                ->from('no-reply@wellcare.tn')
                ->to($to)
                ->subject('Rappel: Votre rendez-vous WellCare est demain')
                ->text($texte)
                ->html($htmlContent);
            
            $this->mailer->send($email);
            
            $this->logNotification($consultation, 'email', $to, $texte, 'envoye', 'rappel');
            
        } catch (\Exception $e) {
            $this->logNotification($consultation, 'email', $to, $texte, 'echec', 'rappel', $e->getMessage());
        }
    }

    /**
     * Journalise la notification en base de données
     */
    private function logNotification(Consultation $consultation, string $type, string $destinataire, string $contenu, string $statut, string $sousType = null, string $erreur = null): void
    {
        $notification = new Notificationrdv();
        $notification->setConsultation($consultation);
        $notification->setType($type);
        $notification->setMessage($contenu);
        $notification->setStatut($statut);
        $notification->setSentAt($statut === 'envoye' ? new \DateTime() : null);
        
        if ($sousType) {
            $notification->setType($sousType);
        }
        
        $this->em->persist($notification);
        $this->em->flush();
    }

    /**
     * Formate un numéro de téléphone (ajoute indicatif Tunisie si nécessaire)
     */
    private function formatNumero(string $numero): string
    {
        // Nettoyer le numéro (enlever espaces, tirets)
        $numero = (string) preg_replace('/[^0-9+]/', '', $numero);
        
        // Si le numéro commence par 0 (format tunisien)
        if (strlen($numero) === 8 && substr($numero, 0, 1) === '2' || 
            strlen($numero) === 8 && substr($numero, 0, 1) === '5' ||
            strlen($numero) === 8 && substr($numero, 0, 1) === '9') {
            $numero = '+216' . $numero;
        }
        
        return $numero;
    }
}
