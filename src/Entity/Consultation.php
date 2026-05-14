<?php

namespace App\Entity;

use App\Repository\ConsultationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ConsultationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Consultation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // CHAMPS EXISTANTS
    #[ORM\Column(length: 255)]
    private ?string $consultation_type = null;

    #[ORM\Column(length: 500)]
    #[Assert\NotBlank(groups: ['clinical_note'], message: 'Le motif de consultation est obligatoire.')]
    #[Assert\Length(max: 500, maxMessage: 'Le motif de consultation ne peut pas depasser {{ limit }} caracteres.', groups: ['clinical_note'])]
    private ?string $reason_for_visit = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\Length(max: 2000, maxMessage: 'Les symptomes ne peuvent pas depasser {{ limit }} caracteres.', groups: ['clinical_note'])]
    private ?string $symptoms_description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date_consultation = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private ?\DateTimeInterface $time_consultation = null;

    #[ORM\Column]
    private ?int $duration = null;

    #[ORM\Column(length: 255)]
    private ?string $location = null;

    #[ORM\Column]
    private ?int $fee = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'Les notes ne peuvent pas depasser {{ limit }} caracteres.', groups: ['clinical_note'])]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updated_at = null;

    #[ORM\Column(length: 50)]
    private ?string $appointment_mode = null;

    // NOUVEAUX CHAMPS POUR SOAP
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(groups: ['clinical_note'], message: 'Le champ Subjectif est obligatoire.')]
    #[Assert\Length(max: 500, maxMessage: 'Le champ Subjectif ne peut pas depasser {{ limit }} caracteres.', groups: ['clinical_note'])]
    private ?string $subjective = null; // S - Subjectif

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(groups: ['clinical_note'], message: 'Le champ Objectif est obligatoire.')]
    #[Assert\Length(max: 500, maxMessage: 'Le champ Objectif ne peut pas depasser {{ limit }} caracteres.', groups: ['clinical_note'])]
    private ?string $objective = null; // O - Objectif

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(groups: ['clinical_note'], message: 'Le champ Evaluation est obligatoire.')]
    #[Assert\Length(max: 500, maxMessage: 'Le champ Evaluation ne peut pas depasser {{ limit }} caracteres.', groups: ['clinical_note'])]
    private ?string $assessment = null; // A - Évaluation

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(groups: ['clinical_note'], message: 'Le champ Plan est obligatoire.')]
    #[Assert\Length(max: 500, maxMessage: 'Le champ Plan ne peut pas depasser {{ limit }} caracteres.', groups: ['clinical_note'])]
    private ?string $plan = null; // P - Plan

    // Stockage JSON pour données structurées
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Assert\Count(
        min: 1,
        max: 5,
        minMessage: 'Au moins un diagnostic est obligatoire.',
        maxMessage: 'Maximum {{ limit }} diagnostics.',
        groups: ['clinical_note']
    )]
    #[Assert\All([
        new Assert\Regex(
            pattern: '/^[A-Z][0-9]{2}$/',
            message: 'Le diagnostic doit suivre le format CIM-10 (ex: A10).'
        ),
    ], groups: ['clinical_note'])]
    /**
     * @var array<string>|null
     * @phpstan-var array<string>|null
     */
    private ?array $diagnoses = []; // Diagnostics (codes CIM-10)

    #[ORM\Column(type: Types::JSON, nullable: true)]
    /**
     * @var array<string, mixed>|null
     * @phpstan-var array<string, mixed>|null
     */
    private ?array $vitals = []; // Signes vitaux structurés

    #[ORM\Column(type: Types::JSON, nullable: true)]
    /**
     * @var array<string, mixed>|null
     * @phpstan-var array<string, mixed>|null
     */
    private ?array $follow_up = []; // Suivi structuré

    /**
     * @var Collection<int, Ordonnance>
     */
    #[ORM\OneToMany(targetEntity: Ordonnance::class, mappedBy: 'consultation', cascade: ['persist', 'remove'])]
    #[Assert\Valid(groups: ['clinical_note'])]
    private Collection $ordonnances;

    /**
     * @var Collection<int, Examens>
     */
    #[ORM\OneToMany(targetEntity: Examens::class, mappedBy: 'consultation', cascade: ['persist', 'remove'])]
    #[Assert\Valid(groups: ['clinical_note'])]
    private Collection $examens;

    // RELATIONSHIP WITH USER (Doctor who performed the consultation)
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'consultations')]
    #[ORM\JoinColumn(name: 'medecin_id', referencedColumnName: 'uuid')]
    private ?User $medecin = null;

    // RELATIONSHIP WITH PATIENT
    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'uuid')]
    private ?Patient $patient = null;

    public function __construct()
    {
        $this->ordonnances = new ArrayCollection();
        $this->examens = new ArrayCollection();
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
        $this->diagnoses = [];
        $this->vitals = [];
        $this->follow_up = [];
    }

    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->updated_at = new \DateTime();
    }

    #[Assert\Callback(groups: ['clinical_note'])]
    public function validateClinicalNote(ExecutionContextInterface $context): void
    {
        $this->validateDiagnoses($context);
        $this->validateVitals($context);
        $this->validateFollowUp($context);
        $this->validateMedications($context);
    }

    private function validateDiagnoses(ExecutionContextInterface $context): void
    {
        if (!is_array($this->diagnoses)) {
            return;
        }

        $seen = [];
        foreach ($this->diagnoses as $index => $diagnosis) {
            if (!is_string($diagnosis)) {
                continue;
            }
            $normalized = strtoupper(trim((string) preg_replace('/\s+/', ' ', (string) $diagnosis)));
            if ($normalized === '') {
                continue;
            }
            if (isset($seen[$normalized])) {
                $context->buildViolation('Le diagnostic ne doit pas etre en doublon.')
                    ->atPath('diagnoses[' . $index . ']')
                    ->addViolation();
            }
            $seen[$normalized] = true;
        }
    }

    private function validateVitals(ExecutionContextInterface $context): void
    {
        if (!is_array($this->vitals)) {
            return;
        }

        $bp = is_array($this->vitals['bloodPressure'] ?? null) ? $this->vitals['bloodPressure'] : [];
        $systolic = $bp['systolic'] ?? $this->vitals['bloodPressureSystolic'] ?? null;
        $diastolic = $bp['diastolic'] ?? $this->vitals['bloodPressureDiastolic'] ?? null;

        $systolicValue = $this->validateNumericRange($context, 'vitals[bloodPressure][systolic]', $systolic, 40, 300, 'La tension systolique doit etre entre 40 et 300.');
        $diastolicValue = $this->validateNumericRange($context, 'vitals[bloodPressure][diastolic]', $diastolic, 20, 200, 'La tension diastolique doit etre entre 20 et 200.');

        if ($systolicValue !== null && $diastolicValue !== null && $systolicValue <= $diastolicValue) {
            $context->buildViolation('La tension systolique doit etre superieure a la diastolique.')
                ->atPath('vitals[bloodPressure]')
                ->addViolation();
        }

        $pulse = $this->vitals['pulse'] ?? $this->vitals['heartRate'] ?? null;
        $this->validateNumericRange($context, 'vitals[pulse]', $pulse, 30, 250, 'Le pouls doit etre entre 30 et 250 bpm.');

        $temperature = $this->vitals['temperature'] ?? null;
        $tempValue = $this->validateNumericRange($context, 'vitals[temperature]', $temperature, 35, 42, 'La temperature doit etre entre 35 et 42 C.');
        if ($tempValue !== null && $tempValue > 38) {
            $context->buildViolation('Alerte: fievre (temperature > 38 C).')
                ->atPath('vitals[temperature]')
                ->addViolation();
        }

        $spo2 = $this->vitals['spo2'] ?? $this->vitals['oxygenSaturation'] ?? null;
        $spo2Value = $this->validateNumericRange($context, 'vitals[spo2]', $spo2, 70, 100, 'La SpO2 doit etre entre 70 et 100%.');
        if ($spo2Value !== null && $spo2Value < 92) {
            $context->buildViolation('Alerte: hypoxie (SpO2 < 92%).')
                ->atPath('vitals[spo2]')
                ->addViolation();
        }
    }

    private function validateFollowUp(ExecutionContextInterface $context): void
    {
        if (!is_array($this->follow_up)) {
            return;
        }

        $dateValue = $this->follow_up['date'] ?? null;
        if ($dateValue === null || $dateValue === '') {
            return;
        }

        try {
            $date = $dateValue instanceof \DateTimeInterface ? $dateValue : new \DateTime((string) $dateValue);
        } catch (\Throwable $e) {
            $context->buildViolation('La date de suivi est invalide.')
                ->atPath('follow_up[date]')
                ->addViolation();
            return;
        }

        $today = new \DateTime('today');
        if ($date < $today) {
            $context->buildViolation('La date de suivi ne peut pas etre dans le passe.')
                ->atPath('follow_up[date]')
                ->addViolation();
        }
    }

    private function validateMedications(ExecutionContextInterface $context): void
    {
        $medications = $this->ordonnances;
        if ($medications->count() > 10) {
            $context->buildViolation('Maximum 10 medicaments par ordonnance.')
                ->atPath('ordonnances')
                ->addViolation();
        }

        $seen = [];
        foreach ($medications as $index => $ordonnance) {
            if (!$ordonnance instanceof Ordonnance) {
                continue;
            }
            $name = $ordonnance->getMedicament();
            $dosage = $ordonnance->getDosage();
            if (!is_string($name) || !is_string($dosage)) {
                continue;
            }
            $normalizedName = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $name)));
            $normalizedDosage = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $dosage)));
            if ($normalizedName === '' || $normalizedDosage === '') {
                continue;
            }
            $key = $normalizedName . '|' . $normalizedDosage;
            if (isset($seen[$key])) {
                $context->buildViolation('Pas de doublons exacts pour les medicaments (nom + dosage).')
                    ->atPath('ordonnances[' . $index . ']')
                    ->addViolation();
            }
            $seen[$key] = true;
        }
    }

    private function validateNumericRange(
        ExecutionContextInterface $context,
        string $path,
        mixed $value,
        float $min,
        float $max,
        string $message
    ): ?float {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            $context->buildViolation('La valeur saisie doit etre un nombre.')
                ->atPath($path)
                ->addViolation();
            return null;
        }
        $number = (float) $value;
        if ($number < $min || $number > $max) {
            $context->buildViolation($message)
                ->atPath($path)
                ->addViolation();
        }
        return $number;
    }

    // GETTERS ET SETTERS EXISTANTS (gardez-les tous)

    // NOUVEAUX GETTERS ET SETTERS POUR SOAP
    public function getSubjective(): ?string
    {
        return $this->subjective;
    }

    public function setSubjective(?string $subjective): static
    {
        $this->subjective = $subjective === null ? null : trim((string) preg_replace('/\s+/', ' ', $subjective));
        return $this;
    }

    public function getObjective(): ?string
    {
        return $this->objective;
    }

    public function setObjective(?string $objective): static
    {
        $this->objective = $objective === null ? null : trim((string) preg_replace('/\s+/', ' ', $objective));
        return $this;
    }

    public function getAssessment(): ?string
    {
        return $this->assessment;
    }

    public function setAssessment(?string $assessment): static
    {
        $this->assessment = $assessment === null ? null : trim((string) preg_replace('/\s+/', ' ', $assessment));
        return $this;
    }

    public function getPlan(): ?string
    {
        return $this->plan;
    }

    public function setPlan(?string $plan): static
    {
        $this->plan = $plan === null ? null : trim((string) preg_replace('/\s+/', ' ', $plan));
        return $this;
    }

    /**
     * @return array<string>|null
     */
    public function getDiagnoses(): ?array
    {
        return $this->diagnoses;
    }

    /**
     * @param array<string>|null $diagnoses
     */
    public function setDiagnoses(?array $diagnoses): static
    {
        if ($diagnoses === null) {
            $this->diagnoses = null;
            return $this;
        }
        $normalized = [];
        foreach ($diagnoses as $diagnosis) {
            if (!is_string($diagnosis)) {
                continue;
            }
            $clean = strtoupper(trim((string) preg_replace('/\s+/', ' ', $diagnosis)));
            if ($clean === '') {
                continue;
            }
            $normalized[] = $clean;
        }
        $this->diagnoses = $normalized;
        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getVitals(): ?array
    {
        return $this->vitals;
    }

    /**
     * @param array<string, mixed>|null $vitals
     */
    public function setVitals(?array $vitals): static
    {
        $this->vitals = $vitals;
        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getFollowUp(): ?array
    {
        return $this->follow_up;
    }

    /**
     * @param array<string, mixed>|null $follow_up
     */
    public function setFollowUp(?array $follow_up): static
    {
        $this->follow_up = $follow_up;
        return $this;
    }

    // Méthodes utilitaires
    public function isComplete(): bool
    {
        return $this->status === 'completed';
    }

    public function getSummary(): string
    {
        return substr((string) $this->reason_for_visit, 0, 100) . '...';
    }

    public function getTypeLabel(): string
    {
        return match($this->consultation_type) {
            'soap' => 'Note SOAP',
            'prescription' => 'Ordonnance',
            'lab' => 'Ordonnance labo',
            default => 'Consultation'
        };
    }

    // Gardez tous les autres getters/setters existants
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConsultationType(): ?string
    {
        return $this->consultation_type;
    }

    public function setConsultationType(string $consultation_type): static
    {
        $this->consultation_type = $consultation_type;
        return $this;
    }

    public function getReasonForVisit(): ?string
    {
        return $this->reason_for_visit;
    }

    public function setReasonForVisit(string $reason_for_visit): static
    {
        $this->reason_for_visit = trim((string) preg_replace('/\s+/', ' ', $reason_for_visit));
        return $this;
    }

    public function getSymptomsDescription(): ?string
    {
        return $this->symptoms_description;
    }

    public function setSymptomsDescription(string $symptoms_description): static
    {
        $this->symptoms_description = trim((string) preg_replace('/\s+/', ' ', $symptoms_description));
        return $this;
    }

    public function getDateConsultation(): ?\DateTimeInterface
    {
        return $this->date_consultation;
    }

    public function setDateConsultation(\DateTimeInterface $date_consultation): static
    {
        $this->date_consultation = $date_consultation;
        return $this;
    }

    public function getTimeConsultation(): ?\DateTimeInterface
    {
        return $this->time_consultation;
    }

    public function setTimeConsultation(\DateTimeInterface $time_consultation): static
    {
        $this->time_consultation = $time_consultation;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getFee(): ?int
    {
        return $this->fee;
    }

    public function setFee(int $fee): static
    {
        $this->fee = $fee;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes === null ? null : trim((string) preg_replace('/\s+/', ' ', $notes));
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;
        return $this;
    }

    public function getAppointmentMode(): ?string
    {
        return $this->appointment_mode;
    }

    public function setAppointmentMode(string $appointment_mode): static
    {
        $this->appointment_mode = $appointment_mode;
        return $this;
    }

    /**
     * @return Collection<int, Ordonnance>
     */
    public function getOrdonnances(): Collection
    {
        return $this->ordonnances;
    }

    public function addOrdonnance(Ordonnance $ordonnance): static
    {
        if (!$this->ordonnances->contains($ordonnance)) {
            $this->ordonnances->add($ordonnance);
            $ordonnance->setConsultation($this);
        }

        return $this;
    }

    public function removeOrdonnance(Ordonnance $ordonnance): static
    {
        if ($this->ordonnances->removeElement($ordonnance)) {
            if ($ordonnance->getConsultation() === $this) {
                $ordonnance->setConsultation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Examens>
     */
    public function getExamens(): Collection
    {
        return $this->examens;
    }

    public function addExamen(Examens $examen): static
    {
        if (!$this->examens->contains($examen)) {
            $this->examens->add($examen);
            $examen->setConsultation($this);
        }

        return $this;
    }

    public function removeExamen(Examens $examen): static
    {
        if ($this->examens->removeElement($examen)) {
            if ($examen->getConsultation() === $this) {
                $examen->setConsultation(null);
            }
        }

        return $this;
    }

    // USER RELATIONSHIP GETTERS AND SETTERS
    public function getMedecin(): ?User
    {
        return $this->medecin;
    }

    public function setMedecin(?User $medecin): static
    {
        $this->medecin = $medecin;
        return $this;
    }

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): static
    {
        $this->patient = $patient;
        return $this;
    }

    // BACKWARD COMPATIBILITY ALIASES
    /**
     * @deprecated Use getOrdonnances() instead
     */
    public function getGenere(): Collection
    {
        return $this->ordonnances;
    }

    /**
     * @deprecated Use getExamens() instead
     */
    public function getPrescrit(): Collection
    {
        return $this->examens;
    }
}
