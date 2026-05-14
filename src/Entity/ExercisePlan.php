<?php

namespace App\Entity;

use App\Repository\ExercisePlanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExercisePlanRepository::class)]
class ExercisePlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'exercisePlans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Goal $goal = null;

    #[ORM\Column]
    private ?int $weekNumber = null;

    #[ORM\Column(type: Types::JSON)]
    /**
     * @var array<string, mixed>
     * @phpstan-var array<string, mixed>
     */
    private array $exercises = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $focus = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $coachNotes = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGoal(): ?Goal
    {
        return $this->goal;
    }

    public function setGoal(?Goal $goal): static
    {
        $this->goal = $goal;
        return $this;
    }

    public function getWeekNumber(): ?int
    {
        return $this->weekNumber;
    }

    public function setWeekNumber(int $weekNumber): static
    {
        $this->weekNumber = $weekNumber;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExercises(): array
    {
        return $this->exercises;
    }

    /**
     * @param array<string, mixed> $exercises
     */
    public function setExercises(array $exercises): static
    {
        $this->exercises = $exercises;
        return $this;
    }

    public function getFocus(): ?string
    {
        return $this->focus;
    }

    public function setFocus(?string $focus): static
    {
        $this->focus = $focus;
        return $this;
    }

    public function getCoachNotes(): ?string
    {
        return $this->coachNotes;
    }

    public function setCoachNotes(?string $coachNotes): static
    {
        $this->coachNotes = $coachNotes;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}