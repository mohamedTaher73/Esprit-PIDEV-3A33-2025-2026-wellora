<?php

namespace App\Entity;

use App\Repository\GoalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: GoalRepository::class)]
class Goal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // 1. Informations de base
    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: "Le titre de l'objectif est requis.")]
    #[Assert\Length(
        max: 150,
        maxMessage: "Le titre de l'objectif ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "La description de l'objectif ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "La catégorie de l'objectif est requise.")]
    private ?string $category = null;   

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Le statut de l'objectif est requis.")]
    #[Assert\Choice(
        choices: ['PENDING', 'in progress', 'completed'],
        message: "Le statut doit être l'une des suivantes : PENDING, in progress, completed."
    )]
    private ?string $status = 'PENDING';

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de début de l'objectif est requise.")]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\Expression(
        "this.getEndDate() === null or this.getEndDate() >= this.getStartDate()",
        message: "La date de fin doit être postérieure ou égale à la date de début."
    )]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    // 2. Raison / Pourquoi
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: "La raison de l'objectif ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $relevant = null;

    // 3. Engagement utilisateur
    private ?bool $userCommitment = false;

    // 4. Niveau de difficulté
    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['Beginner', 'Intermediate', 'Advanced'],
        message: "Le niveau de difficulté doit être l'un des suivants : Beginner, Intermediate, Advanced."
    )]
    private ?string $difficultyLevel = null;

    // 5. Public cible
    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['General', 'Weight Loss', 'Muscle Gain', 'Endurance', 'Flexibility', 'Rehabilitation'],
        message: "Le public cible doit être l'un des suivants : General, Weight Loss, Muscle Gain, Endurance, Flexibility, Rehabilitation."
    )]
    private ?string $targetAudience = null;

    // 2. Objectif mesurable (tracking principal)
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $targetValue = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\PositiveOrZero]
    private ?float $currentValue = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: "Progress must be between 0 and 100")]
    private ?int $progress = 0;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $goalType = null;

    // 3. Planning & organisation
    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['Daily', 'Weekly', 'Monthly', 'Custom'],
        message: "La fréquence doit être l'une des suivantes : Daily, Weekly, Monthly, Custom."
    )]
    private ?string $frequency = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\PositiveOrZero(message: "Sessions per week must be zero or positive")]
    private ?int $sessionsPerWeek = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\PositiveOrZero(message: "Session duration must be zero or positive")]
    private ?int $sessionDuration = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Assert\Time(message: "Preferred time must be a valid time")]
    private ?\DateTimeInterface $preferredTime = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\PositiveOrZero(message: "Duration in weeks must be zero or positive")]
    private ?int $durationWeeks = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $restDays = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    /**
     * @var array<int, string>
     * @phpstan-var array<int, string>
     */
    private array $preferredDays = [];

    // 6. Santé & contraintes utilisateur
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\Positive]
    private ?float $weightStart = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    #[Assert\Positive]
    private ?float $weightTarget = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Positive]
    private ?int $height = null;
#[ORM\Column(type: Types::TEXT, nullable: true)]
private ?string $aiCoachAdvice = null;  // Conseil généré par l'IA pour le coach

#[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
private ?\DateTimeInterface $lastAiAnalysis = null;  // Date de la dernière analyse

#[ORM\Column(type: Types::JSON, nullable: true)]
/**
 * @var array<string, mixed>|null
 * @phpstan-var array<string, mixed>|null
 */
private ?array $aiMetrics = null; 

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\PositiveOrZero(message: "Calories target must be zero or positive")]
    private ?int $caloriesTarget = null;

   
    /**
     * @var Collection<int, DailyPlan>
     */
    #[ORM\OneToMany(targetEntity: DailyPlan::class, mappedBy: 'goal')]
    private Collection $dailyplan;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
private ?string $coachNotes = null;
/**
 * @var Collection<int, ExercisePlan>
 */
#[ORM\OneToMany(targetEntity: ExercisePlan::class, mappedBy: 'goal', cascade: ['persist', 'remove'])]
private Collection $exercisePlans;
#[ORM\Column(type: Types::INTEGER, nullable: true)]
private ?int $patientSatisfaction = null;
#[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'goals')]
#[ORM\JoinColumn(
    referencedColumnName: 'uuid', // Indique que la clé étrangère pointe vers 'uuid' de la table users
    nullable: false               // Empêche d'avoir un Goal sans User
)]
private ?User $patient = null;

#[ORM\Column(length: 255)]

private ?string $coachId = null;
    public function __construct()
    {
        $this->dailyplan = new ArrayCollection();
        $this->date = new \DateTime();
        $this->startDate = new \DateTime();
        $this->status = 'PENDING';
        $this->aiMetrics = null; 
        $this->exercisePlans = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        if ($this->endDate && $this->endDate < $this->startDate) {
            $context->buildViolation('End date must be after start date')
                ->atPath('endDate')
                ->addViolation();
        }
    }
    // Getters et Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
{
    $this->category = $category;
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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
{
    $this->date = $date;
    return $this;
}

    public function getRelevant(): ?string
    {
        return $this->relevant;
    }

    public function setRelevant(?string $relevant): static
    {
        $this->relevant = $relevant;

        return $this;
    }

    public function getUserCommitment(): ?bool
    {
        return $this->userCommitment;
    }

    public function setUserCommitment(bool $userCommitment): static
    {
        $this->userCommitment = $userCommitment;

        return $this;
    }

    public function getDifficultyLevel(): ?string
    {
        return $this->difficultyLevel;
    }

    public function setDifficultyLevel(?string $difficultyLevel): static
    {
        $this->difficultyLevel = $difficultyLevel;

        return $this;
    }

    public function getTargetAudience(): ?string
    {
        return $this->targetAudience;
    }

    public function setTargetAudience(?string $targetAudience): static
    {
        $this->targetAudience = $targetAudience;

        return $this;
    }

    public function getTargetValue(): ?float
    {
        return $this->targetValue;
    }

    public function setTargetValue(?float $targetValue): static
    {
        $this->targetValue = $targetValue;

        return $this;
    }

    public function getCurrentValue(): ?float
    {
        return $this->currentValue;
    }

    public function setCurrentValue(?float $currentValue): static
    {
        $this->currentValue = $currentValue;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getProgress(): ?int
    {
        return $this->progress;
    }

    public function setProgress(?int $progress): static
    {
        $this->progress = $progress;

        return $this;
    }

    public function getGoalType(): ?string
    {
        return $this->goalType;
    }

    public function setGoalType(?string $goalType): static
    {
        $this->goalType = $goalType;

        return $this;
    }

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function setFrequency(?string $frequency): static
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function getSessionsPerWeek(): ?int
    {
        return $this->sessionsPerWeek;
    }

    public function setSessionsPerWeek(?int $sessionsPerWeek): static
    {
        $this->sessionsPerWeek = $sessionsPerWeek;

        return $this;
    }

    public function getSessionDuration(): ?int
    {
        return $this->sessionDuration;
    }

    public function setSessionDuration(?int $sessionDuration): static
    {
        $this->sessionDuration = $sessionDuration;

        return $this;
    }

    public function getPreferredTime(): ?\DateTimeInterface
    {
        return $this->preferredTime;
    }

    public function setPreferredTime(?\DateTimeInterface $preferredTime): static
    {
        $this->preferredTime = $preferredTime;

        return $this;
    }

    public function getDurationWeeks(): ?int
    {
        return $this->durationWeeks;
    }

    public function setDurationWeeks(?int $durationWeeks): static
    {
        $this->durationWeeks = $durationWeeks;

        return $this;
    }

    public function getRestDays(): ?int
    {
        return $this->restDays;
    }

    public function setRestDays(?int $restDays): static
    {
        $this->restDays = $restDays;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getPreferredDays(): array
    {
        return $this->preferredDays;
    }

    /**
     * @param array<int, string> $preferredDays
     */
    public function setPreferredDays(array $preferredDays): static
    {
        $this->preferredDays = $preferredDays;

        return $this;
    }

    public function getWeightStart(): ?float
    {
        return $this->weightStart;
    }

    public function setWeightStart(?float $weightStart): static
    {
        $this->weightStart = $weightStart;

        return $this;
    }

    public function getWeightTarget(): ?float
    {
        return $this->weightTarget;
    }

    public function setWeightTarget(?float $weightTarget): static
    {
        $this->weightTarget = $weightTarget;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getCaloriesTarget(): ?int
    {
        return $this->caloriesTarget;
    }

    public function setCaloriesTarget(?int $caloriesTarget): static
    {
        $this->caloriesTarget = $caloriesTarget;

        return $this;
    }

    

    /**
     * @return Collection<int, DailyPlan>
     */
    public function getDailyplan(): Collection
    {
        return $this->dailyplan;
    }

    public function addDailyplan(DailyPlan $dailyplan): static
    {
        if (!$this->dailyplan->contains($dailyplan)) {
            $this->dailyplan->add($dailyplan);
            $dailyplan->setGoal($this);
        }

        return $this;
    }

    public function removeDailyplan(DailyPlan $dailyplan): static
    {
        if ($this->dailyplan->removeElement($dailyplan)) {
            if ($dailyplan->getGoal() === $this) {
                $dailyplan->setGoal(null);
            }
        }

        return $this;
    }

    public function getPatient(): ?User
    {
        return $this->patient;
    }

    public function setPatient(?User $patient): static
    {
        $this->patient = $patient;

        return $this;
    }

    public function getCoachId(): ?string
    {
        return $this->coachId;
    }

    public function setCoachId(string $coachId): static
    {
        $this->coachId = $coachId;

        return $this;
    }
    // Ajoute avec les autres getters/setters
public function getAiCoachAdvice(): ?string
{
    return $this->aiCoachAdvice;
}

public function setAiCoachAdvice(?string $aiCoachAdvice): self
{
    $this->aiCoachAdvice = $aiCoachAdvice;
    return $this;
}

public function getLastAiAnalysis(): ?\DateTimeInterface
{
    return $this->lastAiAnalysis;
}

public function setLastAiAnalysis(?\DateTimeInterface $lastAiAnalysis): self
{
    $this->lastAiAnalysis = $lastAiAnalysis;
    return $this;
}

public function getAiMetrics(): ?array  // ← Ajoute ? devant array
{
    return $this->aiMetrics;
}

/**
 * @param array<string, mixed>|null $aiMetrics
 * @phpstan-param array<string, mixed>|null $aiMetrics
 */
public function setAiMetrics(?array $aiMetrics): self  // ← Ajoute ? devant array
{
    $this->aiMetrics = $aiMetrics;
    return $this;
}
public function getCoachNotes(): ?string
{
    return $this->coachNotes;
}

public function setCoachNotes(?string $coachNotes): self
{
    $this->coachNotes = $coachNotes;
    return $this;
}

public function getPatientSatisfaction(): ?int
{
    return $this->patientSatisfaction;
}

public function setPatientSatisfaction(?int $patientSatisfaction): self
{
    $this->patientSatisfaction = $patientSatisfaction;
    return $this;
}
/**
 * @return Collection<int, ExercisePlan>
 */
public function getExercisePlans(): Collection
{
    return $this->exercisePlans;
}

public function addExercisePlan(ExercisePlan $exercisePlan): static
{
    if (!$this->exercisePlans->contains($exercisePlan)) {
        $this->exercisePlans->add($exercisePlan);
        $exercisePlan->setGoal($this);
    }
    return $this;
}

public function removeExercisePlan(ExercisePlan $exercisePlan): static
{
    if ($this->exercisePlans->removeElement($exercisePlan)) {
        if ($exercisePlan->getGoal() === $this) {
            $exercisePlan->setGoal(null);
        }
    }
    return $this;
}
/**
 * Calcule la progression basée sur les plans créés jusqu'à aujourd'hui
 */
public function calculateProgressFromPlans(): float
{
    $today = new \DateTime();
    $today->setTime(0, 0, 0);
    
    $totalPlansCreated = 0;
    $completedPlans = 0;
    
    foreach ($this->getDailyplan() as $plan) {
        $planDate = $plan->getDate();
        
        // Ne compter que les plans dont la date est passée ou aujourd'hui
        if ($planDate <= $today) {
            $totalPlansCreated++;
            
            if ($plan->getStatus() === 'completed') {
                $completedPlans++;
            }
        }
    }
    
    if ($totalPlansCreated === 0) {
        return 0;
    }
    
    return round(($completedPlans / $totalPlansCreated) * 100, 1);
}
}