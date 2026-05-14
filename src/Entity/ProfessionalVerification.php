<?php

namespace App\Entity;

use App\Repository\ProfessionalVerificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfessionalVerificationRepository::class)]
#[ORM\Table(name: 'professional_verifications')]
class ProfessionalVerification
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_MANUAL_REVIEW = 'manual_review';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36, nullable: false)]
    private ?string $professionalUuid = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $professionalEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $licenseNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $specialty = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $diplomaPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $diplomaFilename = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $extractedData = null;

    #[ORM\Column(nullable: true)]
    private ?int $confidenceScore = null;

    #[ORM\Column(length: 20, nullable: false)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $validationDetails = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $forgeryIndicators = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $rejectionReason = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $verifiedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reviewedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProfessionalUuid(): ?string
    {
        return $this->professionalUuid;
    }

    public function setProfessionalUuid(string $professionalUuid): self
    {
        $this->professionalUuid = $professionalUuid;
        return $this;
    }

    public function getProfessionalEmail(): ?string
    {
        return $this->professionalEmail;
    }

    public function setProfessionalEmail(?string $email): self
    {
        $this->professionalEmail = $email;
        return $this;
    }

    public function getLicenseNumber(): ?string
    {
        return $this->licenseNumber;
    }

    public function setLicenseNumber(?string $licenseNumber): self
    {
        $this->licenseNumber = $licenseNumber;
        return $this;
    }

    public function getSpecialty(): ?string
    {
        return $this->specialty;
    }

    public function setSpecialty(?string $specialty): self
    {
        $this->specialty = $specialty;
        return $this;
    }

    public function getDiplomaPath(): ?string
    {
        return $this->diplomaPath;
    }

    public function setDiplomaPath(?string $diplomaPath): self
    {
        $this->diplomaPath = $diplomaPath;
        return $this;
    }

    public function getDiplomaFilename(): ?string
    {
        return $this->diplomaFilename;
    }

    public function setDiplomaFilename(?string $diplomaFilename): self
    {
        $this->diplomaFilename = $diplomaFilename;
        return $this;
    }

    public function getExtractedData(): ?array
    {
        return $this->extractedData;
    }

    public function setExtractedData(?array $extractedData): self
    {
        $this->extractedData = $extractedData;
        return $this;
    }

    public function getConfidenceScore(): ?int
    {
        return $this->confidenceScore;
    }

    public function setConfidenceScore(?int $confidenceScore): self
    {
        $this->confidenceScore = $confidenceScore;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'En attente',
            self::STATUS_PROCESSING => 'En cours',
            self::STATUS_VERIFIED => 'Vérifié',
            self::STATUS_REJECTED => 'Rejeté',
            self::STATUS_MANUAL_REVIEW => 'Revision manuelle',
            default => $this->status,
        };
    }

    public function getValidationDetails(): ?array
    {
        return $this->validationDetails;
    }

    public function setValidationDetails(?array $validationDetails): self
    {
        $this->validationDetails = $validationDetails;
        return $this;
    }

    public function getForgeryIndicators(): ?array
    {
        return $this->forgeryIndicators;
    }

    public function setForgeryIndicators(?array $forgeryIndicators): self
    {
        $this->forgeryIndicators = $forgeryIndicators;
        return $this;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function setRejectionReason(?string $rejectionReason): self
    {
        $this->rejectionReason = $rejectionReason;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getVerifiedAt(): ?\DateTimeInterface
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?\DateTimeInterface $verifiedAt): self
    {
        $this->verifiedAt = $verifiedAt;
        return $this;
    }

    public function getReviewedBy(): ?string
    {
        return $this->reviewedBy;
    }

    public function setReviewedBy(?string $reviewedBy): self
    {
        $this->reviewedBy = $reviewedBy;
        return $this;
    }

    /**
     * Get the full URL for the diploma file
     */
    public function getDiplomaUrl(): ?string
    {
        if (!$this->diplomaFilename) {
            return null;
        }
        return '/uploads/diplomas/' . $this->diplomaFilename;
    }
}
