<?php

namespace App\Entity;

use App\Repository\FoodLogRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FoodLogRepository::class)]
#[ORM\Table(name: 'food_logs')]
class FoodLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'foodLogs')]
    #[ORM\JoinColumn(name: 'user_uuid', referencedColumnName: 'uuid')]
    private ?User $user = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(length: 50)]
    private ?string $mealType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'foodLog', targetEntity: FoodItem::class, cascade: ['persist', 'remove'])]
    /**
     * @var Collection<int, FoodItem>
     * @phpstan-var Collection<int, FoodItem>
     */
    private Collection $foodItems;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $totalCalories = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 1, nullable: true)]
    private ?string $totalProtein = '0.0';

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 1, nullable: true)]
    private ?string $totalCarbs = '0.0';

    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 1, nullable: true)]
    private ?string $totalFats = '0.0';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->foodItems = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int|string $userId): static
    {
        $this->userId = is_string($userId) ? (int) hexdec(substr($userId, 0, 8)) : $userId;

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getMealType(): ?string
    {
        return $this->mealType;
    }

    public function setMealType(string $mealType): static
    {
        $this->mealType = $mealType;

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

    /**
     * @return Collection<int, FoodItem>
     */
    public function getFoodItems(): Collection
    {
        return $this->foodItems;
    }

    public function addFoodItem(FoodItem $foodItem): static
    {
        if (!$this->foodItems->contains($foodItem)) {
            $this->foodItems->add($foodItem);
            $foodItem->setFoodLog($this);
        }

        return $this;
    }

    public function removeFoodItem(FoodItem $foodItem): static
    {
        if ($this->foodItems->removeElement($foodItem)) {
            if ($foodItem->getFoodLog() === $this) {
                $foodItem->setFoodLog(null);
            }
        }

        return $this;
    }

    public function getTotalCalories(): ?int
    {
        return $this->totalCalories;
    }

    public function setTotalCalories(?int $totalCalories): static
    {
        $this->totalCalories = $totalCalories;

        return $this;
    }

    public function getTotalProtein(): ?string
    {
        return $this->totalProtein;
    }

    public function setTotalProtein(?string $totalProtein): static
    {
        $this->totalProtein = $totalProtein;

        return $this;
    }

    public function getTotalCarbs(): ?string
    {
        return $this->totalCarbs;
    }

    public function setTotalCarbs(?string $totalCarbs): static
    {
        $this->totalCarbs = $totalCarbs;

        return $this;
    }

    public function getTotalFats(): ?string
    {
        return $this->totalFats;
    }

    public function setTotalFats(?string $totalFats): static
    {
        $this->totalFats = $totalFats;

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

    /**
     * Calculate totals from food items
     */
    public function calculateTotals(): void
    {
        $totalCalories = 0;
        $totalProtein = 0.0;
        $totalCarbs = 0.0;
        $totalFats = 0.0;

        foreach ($this->foodItems as $item) {
            $totalCalories += $item->getCalories();
            $totalProtein += floatval($item->getProtein());
            $totalCarbs += floatval($item->getCarbs());
            $totalFats += floatval($item->getFats());
        }

        $this->totalCalories = $totalCalories;
        $this->totalProtein = number_format($totalProtein, 1);
        $this->totalCarbs = number_format($totalCarbs, 1);
        $this->totalFats = number_format($totalFats, 1);
    }
}
