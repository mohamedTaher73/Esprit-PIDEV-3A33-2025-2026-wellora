<?php

namespace App\Entity;

use App\Repository\NotificationrdvRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationrdvRepository::class)]
class Notificationrdv
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $message = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $sent_at = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'notifie_uuid', referencedColumnName: 'uuid')]
    private ?User $notifie = null;

    #[ORM\ManyToOne(targetEntity: Consultation::class, cascade: ['remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Consultation $consultation = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getSentAt(): ?\DateTime
    {
        return $this->sent_at;
    }

    public function setSentAt(?\DateTime $sent_at): static
    {
        $this->sent_at = $sent_at;

        return $this;
    }

    public function getNotifie(): ?User
    {
        return $this->notifie;
    }

    public function setNotifie(?User $notifie): static
    {
        $this->notifie = $notifie;

        return $this;
    }

    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(?Consultation $consultation): static
    {
        $this->consultation = $consultation;

        return $this;
    }
}
