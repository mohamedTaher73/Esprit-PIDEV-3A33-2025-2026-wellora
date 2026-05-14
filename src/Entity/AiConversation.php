<?php

namespace App\Entity;

use App\Repository\AiConversationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AiConversationRepository::class)]
#[ORM\Table(name: 'ai_conversations')]
class AiConversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userMessage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aiResponse = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    /**
     * @var array<string, mixed>|null
     * @phpstan-var array<string, mixed>|null
     */
    private ?array $metadata = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $intent = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $caloriesContext = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $proteinContext = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $carbsContext = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $fatsContext = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isStarred = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

    public function getUserMessage(): ?string
    {
        return $this->userMessage;
    }

    public function setUserMessage(?string $userMessage): static
    {
        $this->userMessage = $userMessage;

        return $this;
    }

    public function getAiResponse(): ?string
    {
        return $this->aiResponse;
    }

    public function setAiResponse(?string $aiResponse): static
    {
        $this->aiResponse = $aiResponse;

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

    public function getIntent(): ?string
    {
        return $this->intent;
    }

    public function setIntent(?string $intent): static
    {
        $this->intent = $intent;

        return $this;
    }

    public function getCaloriesContext(): ?int
    {
        return $this->caloriesContext;
    }

    public function setCaloriesContext(?int $caloriesContext): static
    {
        $this->caloriesContext = $caloriesContext;

        return $this;
    }

    public function getProteinContext(): ?int
    {
        return $this->proteinContext;
    }

    public function setProteinContext(?int $proteinContext): static
    {
        $this->proteinContext = $proteinContext;

        return $this;
    }

    public function getCarbsContext(): ?int
    {
        return $this->carbsContext;
    }

    public function setCarbsContext(?int $carbsContext): static
    {
        $this->carbsContext = $carbsContext;

        return $this;
    }

    public function getFatsContext(): ?int
    {
        return $this->fatsContext;
    }

    public function setFatsContext(?int $fatsContext): static
    {
        $this->fatsContext = $fatsContext;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isStarred(): bool
    {
        return $this->isStarred;
    }

    public function setIsStarred(bool $isStarred): static
    {
        $this->isStarred = $isStarred;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }
}
