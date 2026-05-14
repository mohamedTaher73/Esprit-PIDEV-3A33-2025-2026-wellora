<?php

namespace App\Entity;

use App\Repository\NutritionConsultationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NutritionConsultationRepository::class)]
#[ORM\Table(name: 'nutrition_consultations')]
class NutritionConsultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'nutritionConsultations')]
    #[ORM\JoinColumn(name: 'patient_uuid', referencedColumnName: 'uuid')]
    private ?User $patient = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'nutritionConsultationsGiven')]
    #[ORM\JoinColumn(name: 'nutritionist_uuid', referencedColumnName: 'uuid')]
    private ?User $nutritionist = null;

    #[ORM\Column(name: 'patient_id')]
    private ?int $patientId = null;

    #[ORM\Column(name: 'nutritionist_id')]
    private ?int $nutritionistId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'scheduled_at')]
    private ?\DateTimeInterface $scheduledAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(length: 100)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'created_at')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'patient_name', length: 255, nullable: true)]
    private ?string $patientName = null;

    #[ORM\Column(name: 'nutritionist_name', length: 255, nullable: true)]
    private ?string $nutritionistName = null;

    #[ORM\Column(name: 'price', nullable: true)]
    private ?float $price = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'pending';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPatientId(): ?int
    {
        return $this->patientId;
    }

    public function setPatientId(int $patientId): self
    {
        $this->patientId = $patientId;
        return $this;
    }

    public function getNutritionistId(): ?int
    {
        return $this->nutritionistId;
    }

    public function setNutritionistId(int $nutritionistId): self
    {
        $this->nutritionistId = $nutritionistId;
        return $this;
    }

    public function getPatient(): ?User
    {
        return $this->patient;
    }

    public function setPatient(?User $patient): self
    {
        $this->patient = $patient;
        return $this;
    }

    public function getNutritionist(): ?User
    {
        return $this->nutritionist;
    }

    public function setNutritionist(?User $nutritionist): self
    {
        $this->nutritionist = $nutritionist;
        return $this;
    }

    public function getScheduledAt(): ?\DateTimeInterface
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(\DateTimeInterface $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getPatientName(): ?string
    {
        return $this->patientName;
    }

    public function setPatientName(?string $patientName): self
    {
        $this->patientName = $patientName;
        return $this;
    }

    public function getNutritionistName(): ?string
    {
        return $this->nutritionistName;
    }

    public function setNutritionistName(?string $nutritionistName): self
    {
        $this->nutritionistName = $nutritionistName;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;
        return $this;
    }

    /**
     * Get type display name
     */
    public function getTypeName(): string
    {
        return match($this->type) {
            'initial' => 'Consultation initiale',
            'followup' => 'Suivi mensuel',
            'weekly' => 'Suivi hebdomadaire',
            'review' => 'Bilan nutritionnel',
            'emergency' => 'Consultation d\'urgence',
            default => 'Consultation'
        };
    }

    /**
     * Get status display name
     */
    public function getStatusName(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
            default => $this->status ?? 'Inconnu'
        };
    }

    /**
     * Get formatted time
     */
    public function getFormattedTime(): string
    {
        if (!$this->scheduledAt) return '';
        return $this->scheduledAt->format('H:i');
    }

    /**
     * Get formatted date
     */
    public function getFormattedDate(): string
    {
        if (!$this->scheduledAt) return '';
        return $this->scheduledAt->format('d/m/Y');
    }
}
