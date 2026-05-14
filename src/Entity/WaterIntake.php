<?php

namespace App\Entity;

use App\Repository\WaterIntakeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WaterIntakeRepository::class)]
#[ORM\Table(name: 'water_intakes')]
class WaterIntake
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'waterIntakes')]
    #[ORM\JoinColumn(name: 'user_uuid', referencedColumnName: 'uuid')]
    private ?User $user = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $glasses = 0;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $milliliters = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
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

    public function getGlasses(): ?int
    {
        return $this->glasses;
    }

    public function setGlasses(int $glasses): static
    {
        $this->glasses = $glasses;

        return $this;
    }

    public function getMilliliters(): ?int
    {
        return $this->milliliters;
    }

    public function setMilliliters(?int $milliliters): static
    {
        $this->milliliters = $milliliters;

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
