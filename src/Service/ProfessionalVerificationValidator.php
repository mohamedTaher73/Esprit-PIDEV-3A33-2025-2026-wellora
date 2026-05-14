<?php

namespace App\Service;

use App\Entity\ProfessionalVerification;

class ProfessionalVerificationValidator
{
    public const RULE_LICENSE_REQUIRED = 'license_required';
    public const RULE_SPECIALTY_REQUIRED = 'specialty_required';
    public const RULE_STATUS_VALID = 'status_valid';

    /**
     * Validates a ProfessionalVerification entity
     * 
     * @param ProfessionalVerification $verification The entity to validate
     * @return array Array of validation errors (empty if valid)
     */
    public function validate(ProfessionalVerification $verification): array
    {
        $errors = [];

        // Rule 1: License number cannot be empty
        if (!$this->validateLicenseRequired($verification)) {
            $errors[self::RULE_LICENSE_REQUIRED] = 'Le numéro de licence est obligatoire pour la vérification.';
        }

        // Rule 2: Specialty cannot be empty
        if (!$this->validateSpecialtyRequired($verification)) {
            $errors[self::RULE_SPECIALTY_REQUIRED] = 'La specialty est obligatoire pour les professionnels.';
        }

        // Rule 3: Status must be valid
        if (!$this->validateStatusValid($verification)) {
            $errors[self::RULE_STATUS_VALID] = 'Le statut doit être valide (pending, processing, verified, rejected, manual_review).';
        }

        return $errors;
    }

    /**
     * Validates that license number is provided
     * 
     * @param ProfessionalVerification $verification
     * @return bool
     */
    public function validateLicenseRequired(ProfessionalVerification $verification): bool
    {
        $licenseNumber = $verification->getLicenseNumber();
        
        // License is required unless status is rejected or pending initial review
        $status = $verification->getStatus();
        if ($status === ProfessionalVerification::STATUS_REJECTED) {
            return true;
        }
        
        return !empty(trim($licenseNumber ?? ''));
    }

    /**
     * Validates that specialty is provided for professionals
     * 
     * @param ProfessionalVerification $verification
     * @return bool
     */
    public function validateSpecialtyRequired(ProfessionalVerification $verification): bool
    {
        $specialty = $verification->getSpecialty();
        
        // Specialty is required for processing and verified statuses
        $status = $verification->getStatus();
        if ($status === ProfessionalVerification::STATUS_PENDING) {
            return true;
        }
        
        return !empty(trim($specialty ?? ''));
    }

    /**
     * Validates that status is one of the allowed values
     * 
     * @param ProfessionalVerification $verification
     * @return bool
     */
    public function validateStatusValid(ProfessionalVerification $verification): bool
    {
        $status = $verification->getStatus();
        
        $validStatuses = [
            ProfessionalVerification::STATUS_PENDING,
            ProfessionalVerification::STATUS_PROCESSING,
            ProfessionalVerification::STATUS_VERIFIED,
            ProfessionalVerification::STATUS_REJECTED,
            ProfessionalVerification::STATUS_MANUAL_REVIEW,
        ];
        
        return in_array($status, $validStatuses, true);
    }

    /**
     * Check if verification is valid for processing
     * 
     * @param ProfessionalVerification $verification
     * @return bool
     */
    public function canBeProcessed(ProfessionalVerification $verification): bool
    {
        return $this->validateLicenseRequired($verification)
            && $this->validateSpecialtyRequired($verification)
            && $this->validateStatusValid($verification);
    }
}
