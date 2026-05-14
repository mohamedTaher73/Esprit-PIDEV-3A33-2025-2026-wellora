<?php

namespace App\Entity;

use App\Repository\NutritionGoalAchievementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NutritionGoalAchievementRepository::class)]
#[ORM\Table(name: 'nutrition_goal_achievements')]
class NutritionGoalAchievement
{
    public const TYPE_STREAK = 'STREAK';
    public const TYPE_MILESTONE = 'MILESTONE';
    public const TYPE_GOAL_COMPLETED = 'GOAL_COMPLETED';
    public const TYPE_CONSISTENCY = 'CONSISTENCY';

    public const TIER_BRONZE = 'BRONZE';
    public const TIER_SILVER = 'SILVER';
    public const TIER_GOLD = 'GOLD';
    public const TIER_PLATINUM = 'PLATINUM';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: NutritionGoal::class, inversedBy: 'achievements')]
    #[ORM\JoinColumn(name: 'goal_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?NutritionGoal $goal = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private ?string $type = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $tier = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $points = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $unlocked = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $unlockedAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    /**
     * @var array<string, mixed>|null
     * @phpstan-var array<string, mixed>|null
     */
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGoal(): ?NutritionGoal
    {
        return $this->goal;
    }

    public function setGoal(?NutritionGoal $goal): static
    {
        $this->goal = $goal;
        return $this;
    }

    /**
     * Alias for setGoal for compatibility
     */
    public function setNutritionGoal(?NutritionGoal $goal): static
    {
        return $this->setGoal($goal);
    }

    /**
     * Alias for getGoal for compatibility
     */
    public function getNutritionGoal(): ?NutritionGoal
    {
        return $this->getGoal();
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function setAchievementType(string $achievementType): static
    {
        $this->type = $achievementType;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
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

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function getTier(): ?string
    {
        return $this->tier;
    }

    public function setTier(?string $tier): static
    {
        $this->tier = $tier;
        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(?int $points): static
    {
        $this->points = $points;
        return $this;
    }

    public function isUnlocked(): bool
    {
        return $this->unlocked;
    }

    public function setUnlocked(bool $unlocked): static
    {
        $this->unlocked = $unlocked;
        if ($unlocked && !$this->unlockedAt) {
            $this->unlockedAt = new \DateTime();
        }
        return $this;
    }

    public function getUnlockedAt(): ?\DateTimeInterface
    {
        return $this->unlockedAt;
    }

    public function setUnlockedAt(?\DateTimeInterface $unlockedAt): static
    {
        $this->unlockedAt = $unlockedAt;
        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
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
}
