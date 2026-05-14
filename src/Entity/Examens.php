<?php

namespace App\Entity;

use App\Repository\ExamensRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ExamensRepository::class)]
class Examens
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // CHAMPS EXISTANTS
    #[ORM\Column(length: 255)]
    private ?string $type_examen = null; // Catégorie: sanguin, radiographie, etc.

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(groups: ['clinical_note'], message: 'La date d examen est obligatoire.')]
    private ?\DateTime $date_examen = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resultat = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'prescrit';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    // NOUVEAUX CHAMPS POUR EXAMENS DÉTAILLÉS
    #[ORM\Column(length: 255)]
    private ?string $nom_examen = null; // Nom spécifique: NFS, Radiographie thoracique, etc.

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date_realisation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $result_file = null; // Nom du fichier PDF/image

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $doctor_analysis = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $doctor_treatment = null;

    // RELATIONSHIP WITH CONSULTATION
    #[ORM\ManyToOne(targetEntity: Consultation::class, inversedBy: 'examens')]
    #[ORM\JoinColumn(name: 'consultation_id', referencedColumnName: 'id', nullable: true)]
    private ?Consultation $consultation = null;

    // RELATIONSHIP WITH USER (Doctor who prescribed)
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'examens')]
    #[ORM\JoinColumn(name: 'medecin_id', referencedColumnName: 'uuid', nullable: true)]
    private ?User $prescribedBy = null;

    public function __construct()
    {
        $this->date_examen = new \DateTime();
        $this->status = 'prescrit';
    }

    #[Assert\Callback(groups: ['clinical_note'])]
    public function validateDateExamen(ExecutionContextInterface $context): void
    {
        if (!$this->date_examen instanceof \DateTimeInterface) {
            return;
        }

        $maxDate = new \DateTime('+2 years');
        if ($this->date_examen > $maxDate) {
            $context->buildViolation('La date d examen ne peut pas depasser 2 ans dans le futur.')
                ->atPath('date_examen')
                ->addViolation();
        }
    }

    // GETTERS ET SETTERS EXISTANTS
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypeExamen(): ?string
    {
        return $this->type_examen;
    }

    public function setTypeExamen(string $type_examen): static
    {
        $this->type_examen = trim((string) preg_replace('/\s+/', ' ', $type_examen));
        return $this;
    }

    public function getDateExamen(): ?\DateTime
    {
        return $this->date_examen;
    }

    public function setDateExamen(\DateTime $date_examen): static
    {
        $this->date_examen = $date_examen;
        return $this;
    }

    public function getResultat(): ?string
    {
        return $this->resultat;
    }

    public function setResultat(?string $resultat): static
    {
        $this->resultat = $resultat === null ? null : trim((string) preg_replace('/\s+/', ' ', $resultat));
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

    public function getNomExamen(): ?string
    {
        return $this->nom_examen;
    }

    public function setNomExamen(string $nom_examen): static
    {
        $this->nom_examen = trim((string) preg_replace('/\s+/', ' ', $nom_examen));
        return $this;
    }

    public function getDateRealisation(): ?\DateTime
    {
        return $this->date_realisation;
    }

    public function setDateRealisation(?\DateTime $date_realisation): static
    {
        $this->date_realisation = $date_realisation;
        return $this;
    }

    public function getResultFile(): ?string
    {
        return $this->result_file;
    }

    public function setResultFile(?string $result_file): static
    {
        $this->result_file = $result_file;
        return $this;
    }

    public function getDoctorAnalysis(): ?string
    {
        return $this->doctor_analysis;
    }

    public function setDoctorAnalysis(?string $doctor_analysis): static
    {
        $this->doctor_analysis = $doctor_analysis === null ? null : trim($doctor_analysis);
        return $this;
    }

    public function getDoctorTreatment(): ?string
    {
        return $this->doctor_treatment;
    }

    public function setDoctorTreatment(?string $doctor_treatment): static
    {
        $this->doctor_treatment = $doctor_treatment === null ? null : trim($doctor_treatment);
        return $this;
    }

    // CONSULTATION RELATIONSHIP
    public function getConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    public function setConsultation(?Consultation $consultation): static
    {
        $this->consultation = $consultation;
        return $this;
    }

    // BACKWARD COMPATIBILITY ALIAS
    /**
     * @deprecated Use getConsultation() instead
     */
    public function getIdConsultation(): ?Consultation
    {
        return $this->consultation;
    }

    /**
     * @deprecated Use setConsultation() instead
     */
    public function setIdConsultation(?Consultation $consultation): static
    {
        return $this->setConsultation($consultation);
    }

    // USER RELATIONSHIP (Doctor who prescribed)
    public function getPrescribedBy(): ?User
    {
        return $this->prescribedBy;
    }

    public function setPrescribedBy(?User $user): static
    {
        $this->prescribedBy = $user;
        return $this;
    }
}
