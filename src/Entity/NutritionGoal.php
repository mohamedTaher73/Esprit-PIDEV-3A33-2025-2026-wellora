<?php

namespace App\Entity;

use App\Repository\NutritionGoalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NutritionGoalRepository::class)]
#[ORM\Table(name: 'nutrition_goals')]
class NutritionGoal
{
    // Constants for status
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_PAUSED = 'PAUSED';
    public const STATUS_CANCELLED = 'CANCELLED';

    // Constants for priority
    public const PRIORITY_LOW = 'LOW';
    public const PRIORITY_MEDIUM = 'MEDIUM';
    public const PRIORITY_HIGH = 'HIGH';

    // Constants for activity level
    public const ACTIVITY_SEDENTARY = 'sedentary';
    public const ACTIVITY_LIGHTLY_ACTIVE = 'light';
    public const ACTIVITY_MODERATELY_ACTIVE = 'moderate';
    public const ACTIVITY_VERY_ACTIVE = 'active';
    public const ACTIVITY_EXTRA_ACTIVE = 'very_active';

    // Constants for goal type
    public const GOAL_TYPE_WEIGHT_LOSS = 'WEIGHT_LOSS';
    public const GOAL_TYPE_WEIGHT_GAIN = 'WEIGHT_GAIN';
    public const GOAL_TYPE_MAINTENANCE = 'MAINTENANCE';
    public const GOAL_TYPE_HEALTHY_EATING = 'HEALTHY_EATING';
    public const GOAL_TYPE_MUSCLE_BUILD = 'MUSCLE_BUILD';
    public const GOAL_TYPE_DIABETES = 'DIABETES';
    public const GOAL_TYPE_CARDIOVASCULAR = 'CARDIOVASCULAR';
    public const GOAL_TYPE_BODY_RECOMPOSITION = 'BODY_RECOMPOSITION';
    public const GOAL_TYPE_BLOOD_SUGAR = 'BLOOD_SUGAR';
    public const GOAL_TYPE_CHOLESTEROL = 'CHOLESTEROL';
    public const GOAL_TYPE_WATER_INTAKE = 'WATER_INTAKE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'nutritionGoals')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'uuid')]
    private ?User $user = null;

    /**
     * @var Collection<int, NutritionGoalAchievement>
     */
    #[ORM\OneToMany(mappedBy: 'goal', targetEntity: NutritionGoalAchievement::class)]
    private Collection $achievements;

    /**
     * @var Collection<int, NutritionGoalAdjustment>
     */
    #[ORM\OneToMany(mappedBy: 'goal', targetEntity: NutritionGoalAdjustment::class)]
    private Collection $adjustments;

    /**
     * @var Collection<int, NutritionGoalMilestone>
     */
    #[ORM\OneToMany(mappedBy: 'goal', targetEntity: NutritionGoalMilestone::class)]
    private Collection $milestones;

    /**
     * @var Collection<int, NutritionGoalProgress>
     */
    #[ORM\OneToMany(mappedBy: 'goal', targetEntity: NutritionGoalProgress::class)]
    private Collection $progress;

    #[ORM\Column(type: Types::STRING, length: 36, nullable: true)]
    private ?string $userId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $goalType = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $caloriesTarget = 2000;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $currentCalories = 2000;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $waterTarget = 8;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $proteinTarget = 120;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $carbsTarget = 200;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $fatsTarget = 65;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $fiberTarget = 25;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $sugarTarget = 25;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $sodiumTarget = 2300;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $weightTarget = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $currentWeight = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $startWeight = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $bmr = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $tdee = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $targetProteinGrams = 120;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $targetCarbGrams = 200;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $targetFatGrams = 65;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $targetProteinPercent = 30;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $targetCarbPercent = 40;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $targetFatPercent = 30;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $targetMealFrequency = 3;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $weeklyWeightChangeTarget = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $expectedWeightChangePerWeek = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $activityLevel = 'moderate';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $status = 'ACTIVE';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $priority = 'MEDIUM';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $targetDate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->achievements = new ArrayCollection();
        $this->adjustments = new ArrayCollection();
        $this->milestones = new ArrayCollection();
        $this->progress = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->startDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(string|null $userId): static
    {
        $this->userId = $userId;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;
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

    public function getCaloriesTarget(): ?int
    {
        return $this->caloriesTarget;
    }

    public function setCaloriesTarget(?int $caloriesTarget): static
    {
        $this->caloriesTarget = $caloriesTarget;
        return $this;
    }

    public function getCurrentCalories(): ?int
    {
        return $this->currentCalories;
    }

    public function setCurrentCalories(?int $currentCalories): static
    {
        $this->currentCalories = $currentCalories;
        return $this;
    }

    public function getTargetCalories(): ?int
    {
        return $this->caloriesTarget;
    }

    public function setTargetCalories(?int $targetCalories): static
    {
        $this->caloriesTarget = $targetCalories;
        return $this;
    }

    public function getWaterTarget(): ?int
    {
        return $this->waterTarget;
    }

    public function setWaterTarget(?int $waterTarget): static
    {
        $this->waterTarget = $waterTarget;
        return $this;
    }

    public function getProteinTarget(): ?int
    {
        return $this->proteinTarget;
    }

    public function setProteinTarget(?int $proteinTarget): static
    {
        $this->proteinTarget = $proteinTarget;
        return $this;
    }

    public function getCarbsTarget(): ?int
    {
        return $this->carbsTarget;
    }

    public function setCarbsTarget(?int $carbsTarget): static
    {
        $this->carbsTarget = $carbsTarget;
        return $this;
    }

    public function getFatsTarget(): ?int
    {
        return $this->fatsTarget;
    }

    public function setFatsTarget(?int $fatsTarget): static
    {
        $this->fatsTarget = $fatsTarget;
        return $this;
    }

    public function getFiberTarget(): ?int
    {
        return $this->fiberTarget;
    }

    public function setFiberTarget(?int $fiberTarget): static
    {
        $this->fiberTarget = $fiberTarget;
        return $this;
    }

    public function getSugarTarget(): ?int
    {
        return $this->sugarTarget;
    }

    public function setSugarTarget(?int $sugarTarget): static
    {
        $this->sugarTarget = $sugarTarget;
        return $this;
    }

    public function getSodiumTarget(): ?int
    {
        return $this->sodiumTarget;
    }

    public function setSodiumTarget(?int $sodiumTarget): static
    {
        $this->sodiumTarget = $sodiumTarget;
        return $this;
    }

    public function getWeightTarget(): ?string
    {
        return $this->weightTarget;
    }

    public function setWeightTarget(?string $weightTarget): static
    {
        $this->weightTarget = $weightTarget;
        return $this;
    }

    public function getCurrentWeight(): ?string
    {
        return $this->currentWeight;
    }

    public function setCurrentWeight(?string $currentWeight): static
    {
        $this->currentWeight = $currentWeight;
        return $this;
    }

    public function getStartWeight(): ?string
    {
        return $this->startWeight;
    }

    public function setStartWeight(?string $startWeight): static
    {
        $this->startWeight = $startWeight;
        return $this;
    }

    public function getBmr(): ?int
    {
        return $this->bmr;
    }

    public function setBmr(?int $bmr): static
    {
        $this->bmr = $bmr;
        return $this;
    }

    public function getTdee(): ?int
    {
        return $this->tdee;
    }

    public function setTdee(?int $tdee): static
    {
        $this->tdee = $tdee;
        return $this;
    }

    public function getTargetProteinGrams(): ?int
    {
        return $this->targetProteinGrams;
    }

    public function setTargetProteinGrams(?int $targetProteinGrams): static
    {
        $this->targetProteinGrams = $targetProteinGrams;
        return $this;
    }

    public function getTargetCarbGrams(): ?int
    {
        return $this->targetCarbGrams;
    }

    public function setTargetCarbGrams(?int $targetCarbGrams): static
    {
        $this->targetCarbGrams = $targetCarbGrams;
        return $this;
    }

    public function getTargetFatGrams(): ?int
    {
        return $this->targetFatGrams;
    }

    public function setTargetFatGrams(?int $targetFatGrams): static
    {
        $this->targetFatGrams = $targetFatGrams;
        return $this;
    }

    public function getTargetProteinPercent(): ?int
    {
        return $this->targetProteinPercent;
    }

    public function setTargetProteinPercent(?int $targetProteinPercent): static
    {
        $this->targetProteinPercent = $targetProteinPercent;
        return $this;
    }

    public function getTargetCarbPercent(): ?int
    {
        return $this->targetCarbPercent;
    }

    public function setTargetCarbPercent(?int $targetCarbPercent): static
    {
        $this->targetCarbPercent = $targetCarbPercent;
        return $this;
    }

    public function getTargetFatPercent(): ?int
    {
        return $this->targetFatPercent;
    }

    public function setTargetFatPercent(?int $targetFatPercent): static
    {
        $this->targetFatPercent = $targetFatPercent;
        return $this;
    }

    public function getTargetMealFrequency(): ?int
    {
        return $this->targetMealFrequency;
    }

    public function setTargetMealFrequency(?int $targetMealFrequency): static
    {
        $this->targetMealFrequency = $targetMealFrequency;
        return $this;
    }

    public function getTargetWaterIntake(): ?int
    {
        return $this->waterTarget;
    }

    public function setTargetWaterIntake(?int $targetWaterIntake): static
    {
        $this->waterTarget = $targetWaterIntake;
        return $this;
    }

    public function getWeeklyWeightChangeTarget(): ?string
    {
        return $this->weeklyWeightChangeTarget;
    }

    public function setWeeklyWeightChangeTarget(?string $weeklyWeightChangeTarget): static
    {
        $this->weeklyWeightChangeTarget = $weeklyWeightChangeTarget;
        return $this;
    }

    public function getExpectedWeightChangePerWeek(): ?string
    {
        return $this->expectedWeightChangePerWeek;
    }

    public function setExpectedWeightChangePerWeek(?string $expectedWeightChangePerWeek): static
    {
        $this->expectedWeightChangePerWeek = $expectedWeightChangePerWeek;
        return $this;
    }

    public function getActivityLevel(): ?string
    {
        return $this->activityLevel;
    }

    public function setActivityLevel(?string $activityLevel): static
    {
        $this->activityLevel = $activityLevel;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(?string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getTargetDate(): ?\DateTimeInterface
    {
        return $this->targetDate;
    }

    public function setTargetDate(?\DateTimeInterface $targetDate): static
    {
        $this->targetDate = $targetDate;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
