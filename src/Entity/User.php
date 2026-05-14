<?php

namespace App\Entity;

use App\Enum\UserRole;
use App\Repository\UserRepository;
use App\Entity\NutritionGoal;
use App\Entity\FoodLog;
use App\Entity\WaterIntake;
use App\Entity\MealPlan;
use App\Entity\NutritionConsultation;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\BackupCodeInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'role', type: 'string')]
#[ORM\DiscriminatorMap([
    'ROLE_PATIENT' => Patient::class,
    'ROLE_MEDECIN' => Medecin::class,
    'ROLE_COACH' => Coach::class,
    'ROLE_NUTRITIONIST' => Nutritionist::class,
    'ROLE_ADMIN' => Administrator::class,
])]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée')]
#[UniqueEntity(fields: ['licenseNumber'], message: 'Ce numéro de licence est déjà utilisé', groups: ['Professional'])]
#[UniqueEntity(fields: ['googleId'], message: 'Ce compte Google est déjà lié à un autre utilisateur')]
abstract class User implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface, BackupCodeInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', unique: true, length: 36)]
    private ?string $uuid = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'Veuillez entrer une adresse email valide')]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le prénom doit contenir au moins 2 caractères')]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    #[Assert\Length(min: 2, max: 100, minMessage: 'Le nom doit contenir au moins 2 caractères')]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthdate = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(pattern: '/^[+]?[0-9\s\-()]+$/', message: 'Le numero de telephone doit contenir uniquement des chiffres et les caracteres + - ( )')]
    private ?string $phone = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(message: 'L\'avatar doit être une URL valide')]
    private ?string $avatarUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'Le numéro de licence est obligatoire', groups: ['Professional'])]
    private ?string $licenseNumber = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column]
    private int $loginAttempts = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lockedUntil = null;

    #[ORM\Column]
    private bool $isEmailVerified = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $emailVerificationExpiresAt = null;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $lastSessionId = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $googleId = null;

    // ============================================
    // Two-Factor Authentication Fields (TOTP)
    // ============================================
    
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isTwoFactorEnabled = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $totpSecret = null;

    // ============================================
    // Backup Codes
    // ============================================
    
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $backupCodes = [];
    
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $plainBackupCodes = [];

    // ============================================
    // Trusted Devices (custom implementation)
    // ============================================
    
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $trustedDevices = [];

    #[ORM\OneToMany(targetEntity: Healthjournal::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    /**
     * @var Collection<int, Healthjournal>
     * @phpstan-var Collection<int, Healthjournal>
     */
    private Collection $healthJournals;

    // Nutrition relationships
    #[ORM\OneToMany(targetEntity: NutritionGoal::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    /**
     * @var Collection<int, NutritionGoal>
     * @phpstan-var Collection<int, NutritionGoal>
     */
    private Collection $nutritionGoals;

    #[ORM\OneToMany(targetEntity: FoodLog::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    /**
     * @var Collection<int, FoodLog>
     * @phpstan-var Collection<int, FoodLog>
     */
    private Collection $foodLogs;

    #[ORM\OneToMany(targetEntity: WaterIntake::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $waterIntakes;

    #[ORM\OneToMany(targetEntity: MealPlan::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private Collection $mealPlans;

    #[ORM\OneToMany(targetEntity: NutritionConsultation::class, mappedBy: 'patient', cascade: ['persist', 'remove'])]
    /**
     * @var Collection<int, NutritionConsultation>
     * @phpstan-var Collection<int, NutritionConsultation>
     */
    private Collection $nutritionConsultations;

    #[ORM\OneToMany(targetEntity: NutritionConsultation::class, mappedBy: 'nutritionist', cascade: ['persist', 'remove'])]
    /**
     * @var Collection<int, NutritionConsultation>
     * @phpstan-var Collection<int, NutritionConsultation>
     */
    private Collection $nutritionConsultationsGiven;
    // Professional properties shared across professional users
    #[ORM\Column(name: 'years_of_experience')]
    #[Assert\PositiveOrZero(message: 'Les années d\'expérience doivent être positives')]
    private int $yearsOfExperience = 0;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(message: 'L\'URL du diplôme doit être valide')]
    private ?string $diplomaUrl = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $specialite = null;

    #[ORM\Column]
    private bool $isVerifiedByAdmin = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $verificationDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $about = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $education = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $certifications = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hospitalAffiliations = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $awards = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $specializations = [];

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $consultationPrice = 120;

    /**
     * @var Collection<int, Consultation>
     */
    #[ORM\OneToMany(targetEntity: Consultation::class, mappedBy: 'medecin')]
    private Collection $consultations;

    /**
     * @var Collection<int, Ordonnance>
     */
    #[ORM\OneToMany(targetEntity: Ordonnance::class, mappedBy: 'prescribedBy')]
    private Collection $ordonnances;

    /**
     * @var Collection<int, Examens>
     */
    #[ORM\OneToMany(targetEntity: Examens::class, mappedBy: 'prescribedBy')]
    private Collection $examens;
    /**
     * @var Collection<int, Goal>
     */
    #[ORM\OneToMany(targetEntity: Goal::class, mappedBy: 'patient')]
    private Collection $goals;

    /**
     * @var Collection<int, Exercises>
     */
    #[ORM\OneToMany(targetEntity: Exercises::class, mappedBy: 'User')]
    private Collection $exercises;

    /**
     * @var Collection<int, DailyPlan>
     */
    #[ORM\OneToMany(targetEntity: DailyPlan::class, mappedBy: 'coach')]
    private Collection $dailyPlans;

    /**
     * @var Collection<int, Conversation>
     */
    #[ORM\OneToMany(targetEntity: Conversation::class, mappedBy: 'coach')]
    private Collection $coach;

    /**
     * @var Collection<int, Conversation>
     */
    #[ORM\OneToMany(targetEntity: Conversation::class, mappedBy: 'patient')]
    private Collection $conversations;

    /**
     * @var Collection<int, Message>
     */
    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'sender')]
    private Collection $messages;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->uuid = Uuid::v4()->toRfc4122();
        $this->healthJournals = new ArrayCollection();
        $this->nutritionGoals = new ArrayCollection();
        $this->foodLogs = new ArrayCollection();
        $this->waterIntakes = new ArrayCollection();
        $this->mealPlans = new ArrayCollection();
        $this->nutritionConsultations = new ArrayCollection();
        $this->nutritionConsultationsGiven = new ArrayCollection();
    }

    /**
     * @return Collection<int, Healthjournal>
     */
    public function getHealthJournals(): Collection
    {
        return $this->healthJournals;
    }

    public function addHealthJournal(Healthjournal $healthJournal): static
    {
        if (!$this->healthJournals->contains($healthJournal)) {
            $this->healthJournals->add($healthJournal);
            $healthJournal->setUser($this);
        }

        return $this;
    }

    public function removeHealthJournal(Healthjournal $healthJournal): static
    {
        if ($this->healthJournals->removeElement($healthJournal)) {
            if ($healthJournal->getUser() === $this) {
                $healthJournal->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, NutritionGoal>
     */
    public function getNutritionGoals(): Collection
    {
        return $this->nutritionGoals;
    }

    public function addNutritionGoal(NutritionGoal $goal): static
    {
        if (!$this->nutritionGoals->contains($goal)) {
            $this->nutritionGoals->add($goal);
            $goal->setUser($this);
        }

        return $this;
    }

    public function removeNutritionGoal(NutritionGoal $goal): static
    {
        if ($this->nutritionGoals->removeElement($goal)) {
            if ($goal->getUser() === $this) {
                $goal->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FoodLog>
     */
    public function getFoodLogs(): Collection
    {
        return $this->foodLogs;
    }

    public function addFoodLog(FoodLog $foodLog): static
    {
        if (!$this->foodLogs->contains($foodLog)) {
            $this->foodLogs->add($foodLog);
            $foodLog->setUser($this);
        }

        return $this;
    }

    public function removeFoodLog(FoodLog $foodLog): static
    {
        if ($this->foodLogs->removeElement($foodLog)) {
            if ($foodLog->getUser() === $this) {
                $foodLog->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, WaterIntake>
     */
    public function getWaterIntakes(): Collection
    {
        return $this->waterIntakes;
    }

    public function addWaterIntake(WaterIntake $waterIntake): static
    {
        if (!$this->waterIntakes->contains($waterIntake)) {
            $this->waterIntakes->add($waterIntake);
            $waterIntake->setUser($this);
        }

        return $this;
    }

    public function removeWaterIntake(WaterIntake $waterIntake): static
    {
        if ($this->waterIntakes->removeElement($waterIntake)) {
            if ($waterIntake->getUser() === $this) {
                $waterIntake->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MealPlan>
     */
    public function getMealPlans(): Collection
    {
        return $this->mealPlans;
    }

    public function addMealPlan(MealPlan $mealPlan): static
    {
        if (!$this->mealPlans->contains($mealPlan)) {
            $this->mealPlans->add($mealPlan);
            $mealPlan->setUser($this);
        }

        return $this;
    }

    public function removeMealPlan(MealPlan $mealPlan): static
    {
        if ($this->mealPlans->removeElement($mealPlan)) {
            if ($mealPlan->getUser() === $this) {
                $mealPlan->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, NutritionConsultation>
     */
    public function getNutritionConsultations(): Collection
    {
        return $this->nutritionConsultations;
    }

    public function addNutritionConsultation(NutritionConsultation $consultation): static
    {
        if (!$this->nutritionConsultations->contains($consultation)) {
            $this->nutritionConsultations->add($consultation);
            $consultation->setPatient($this);
        }

        return $this;
    }

    public function removeNutritionConsultation(NutritionConsultation $consultation): static
    {
        if ($this->nutritionConsultations->removeElement($consultation)) {
            if ($consultation->getPatient() === $this) {
                $consultation->setPatient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, NutritionConsultation>
     */
    public function getNutritionConsultationsGiven(): Collection
    {
        return $this->nutritionConsultationsGiven;
    }

    public function addNutritionConsultationGiven(NutritionConsultation $consultation): static
    {
        if (!$this->nutritionConsultationsGiven->contains($consultation)) {
            $this->nutritionConsultationsGiven->add($consultation);
            $consultation->setNutritionist($this);
        }

        return $this;
    }

    public function removeNutritionConsultationGiven(NutritionConsultation $consultation): static
    {
        if ($this->nutritionConsultationsGiven->removeElement($consultation)) {
            if ($consultation->getNutritionist() === $this) {
                $consultation->setNutritionist(null);
            }
        }

        return $this;
        $this->consultations = new ArrayCollection();
        $this->ordonnances = new ArrayCollection();
        $this->examens = new ArrayCollection();
        $this->goals = new ArrayCollection();
        $this->exercises = new ArrayCollection();
        $this->dailyPlans = new ArrayCollection();
        $this->coach = new ArrayCollection();
        $this->conversations = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * Returns the user identifier (UUID).
     * Alias for getUuid() for compatibility.
     */
    public function getId(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;
        return $this;
    }


    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getBirthdate(): ?\DateTimeInterface
    {
        return $this->birthdate;
    }

    public function setBirthdate(?\DateTimeInterface $birthdate): self
    {
        $this->birthdate = $birthdate;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getLicenseNumber(): ?string
    {
        return $this->licenseNumber;
    }

    public function setLicenseNumber(?string $licenseNumber): self
    {
        $this->licenseNumber = $licenseNumber;
        return $this;
    }

    public function isIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $resetTokenExpiresAt): self
    {
        $this->resetTokenExpiresAt = $resetTokenExpiresAt;
        return $this;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): self
    {
        $this->lastLoginAt = $lastLoginAt;
        return $this;
    }

    public function getLoginAttempts(): int
    {
        return $this->loginAttempts;
    }

    public function setLoginAttempts(int $loginAttempts): self
    {
        $this->loginAttempts = $loginAttempts;
        return $this;
    }

    public function incrementLoginAttempts(): self
    {
        $this->loginAttempts++;
        return $this;
    }

    public function resetLoginAttempts(): self
    {
        $this->loginAttempts = 0;
        return $this;
    }

    public function getLockedUntil(): ?\DateTimeInterface
    {
        return $this->lockedUntil;
    }

    public function setLockedUntil(?\DateTimeInterface $lockedUntil): self
    {
        $this->lockedUntil = $lockedUntil;
        return $this;
    }

    public function isLocked(): bool
    {
        return $this->lockedUntil !== null && $this->lockedUntil > new \DateTime();
    }

    public function isEmailVerified(): bool
    {
        return $this->isEmailVerified;
    }

    public function setIsEmailVerified(bool $isEmailVerified): self
    {
        $this->isEmailVerified = $isEmailVerified;
        return $this;
    }

    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(?string $emailVerificationToken): self
    {
        $this->emailVerificationToken = $emailVerificationToken;
        return $this;
    }

    public function getEmailVerificationExpiresAt(): ?\DateTimeInterface
    {
        return $this->emailVerificationExpiresAt;
    }

    public function setEmailVerificationExpiresAt(?\DateTimeInterface $emailVerificationExpiresAt): self
    {
        $this->emailVerificationExpiresAt = $emailVerificationExpiresAt;
        return $this;
    }

    public function getYearsOfExperience(): int
    {
        return $this->yearsOfExperience;
    }

    public function setYearsOfExperience(int $yearsOfExperience): self
    {
        $this->yearsOfExperience = $yearsOfExperience;
        return $this;
    }

    public function getDiplomaUrl(): ?string
    {
        return $this->diplomaUrl;
    }

    public function setDiplomaUrl(?string $diplomaUrl): self
    {
        $this->diplomaUrl = $diplomaUrl;
        return $this;
    }

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(?string $specialite): self
    {
        $this->specialite = $specialite;
        return $this;
    }

    public function isVerifiedByAdmin(): bool
    {
        return $this->isVerifiedByAdmin;
    }

    public function setVerifiedByAdmin(bool $isVerifiedByAdmin): self
    {
        $this->isVerifiedByAdmin = $isVerifiedByAdmin;
        return $this;
    }

    public function getVerificationDate(): ?\DateTimeInterface
    {
        return $this->verificationDate;
    }

    public function setVerificationDate(?\DateTimeInterface $verificationDate): self
    {
        $this->verificationDate = $verificationDate;
        return $this;
    }

    public function getAbout(): ?string
    {
        return $this->about;
    }

    public function setAbout(?string $about): self
    {
        $this->about = $about;
        return $this;
    }

    public function getEducation(): ?string
    {
        return $this->education;
    }

    public function setEducation(?string $education): self
    {
        $this->education = $education;
        return $this;
    }

    public function getCertifications(): ?string
    {
        return $this->certifications;
    }

    public function setCertifications(?string $certifications): self
    {
        $this->certifications = $certifications;
        return $this;
    }

    public function getHospitalAffiliations(): ?string
    {
        return $this->hospitalAffiliations;
    }

    public function setHospitalAffiliations(?string $hospitalAffiliations): self
    {
        $this->hospitalAffiliations = $hospitalAffiliations;
        return $this;
    }

    public function getAwards(): ?string
    {
        return $this->awards;
    }

    public function setAwards(?string $awards): self
    {
        $this->awards = $awards;
        return $this;
    }

    public function getSpecializations(): ?array
    {
        return $this->specializations;
    }

    public function setSpecializations(?array $specializations): self
    {
        $this->specializations = $specializations;
        return $this;
    }

    public function getConsultationPrice(): ?int
    {
        return $this->consultationPrice;
    }

    public function setConsultationPrice(?int $consultationPrice): self
    {
        $this->consultationPrice = $consultationPrice;
        return $this;
    }

    public function getLastSessionId(): ?string
    {
        return $this->lastSessionId;
    }

    public function setLastSessionId(?string $lastSessionId): self
    {
        $this->lastSessionId = $lastSessionId;
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;

        return $this;
    }

    /**
     * @return Collection<int, Consultation>
     */
    public function getConsultations(): Collection
    {
        return $this->consultations;
    }

    public function addConsultation(Consultation $consultation): self
    {
        if (!$this->consultations->contains($consultation)) {
            $this->consultations->add($consultation);
            $consultation->setMedecin($this);
        }

        return $this;
    }

    public function removeConsultation(Consultation $consultation): self
    {
        if ($this->consultations->removeElement($consultation)) {
            if ($consultation->getMedecin() === $this) {
                $consultation->setMedecin(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ordonnance>
     */
    public function getOrdonnances(): Collection
    {
        return $this->ordonnances;
    }

    public function addOrdonnance(Ordonnance $ordonnance): self
    {
        if (!$this->ordonnances->contains($ordonnance)) {
            $this->ordonnances->add($ordonnance);
            $ordonnance->setPrescribedBy($this);
        }

        return $this;
    }

    public function removeOrdonnance(Ordonnance $ordonnance): self
    {
        if ($this->ordonnances->removeElement($ordonnance)) {
            if ($ordonnance->getPrescribedBy() === $this) {
                $ordonnance->setPrescribedBy(null);
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

    public function addExamen(Examens $examen): self
    {
        if (!$this->examens->contains($examen)) {
            $this->examens->add($examen);
            $examen->setPrescribedBy($this);
        }

        return $this;
    }

    public function removeExamen(Examens $examen): self
    {
        if ($this->examens->removeElement($examen)) {
            if ($examen->getPrescribedBy() === $this) {
                $examen->setPrescribedBy(null);
            }
        }

        return $this;
    }

    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    // ============================================
    // Two-Factor Authentication Getters/Setters
    // ============================================

    public function isTwoFactorEnabled(): bool
    {
        return $this->isTwoFactorEnabled;
    }

    public function setIsTwoFactorEnabled(bool $isTwoFactorEnabled): self
    {
        $this->isTwoFactorEnabled = $isTwoFactorEnabled;
        return $this;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $totpSecret): self
    {
        $this->totpSecret = $totpSecret;
        return $this;
    }

    // ============================================
    // Backup Codes Getters/Setters
    // ============================================

    public function getBackupCodesArray(): ?array
    {
        return $this->backupCodes;
    }

    public function setBackupCodesArray(?array $backupCodes): self
    {
        $this->backupCodes = $backupCodes;
        return $this;
    }

    // ============================================
    // Trusted Devices Getters/Setters (Custom)
    // ============================================

    public function getTrustedDevicesArray(): ?array
    {
        return $this->trustedDevices;
    }

    public function setTrustedDevicesArray(?array $trustedDevices): self
    {
        $this->trustedDevices = $trustedDevices;
        return $this;
    }

    // ============================================
    // TwoFactorInterface Implementation (TOTP)
    // ============================================

    /**
     * Return true if the user should do TOTP authentication.
     */
    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->isTwoFactorEnabled;
    }

    /**
     * Return the user name. This is used in QR code generation.
     */
    public function getTotpAuthenticationUsername(): string
    {
        return $this->email;
    }

    /**
     * Return the configuration for TOTP authentication.
     */
    public function getTotpAuthenticationConfiguration(): ?\Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface
    {
        // Return configuration if secret exists (during setup or after 2FA is enabled)
        if (!$this->totpSecret) {
            return null;
        }
        
        return new TotpConfiguration(
            $this->totpSecret,
            TotpConfiguration::ALGORITHM_SHA1,
            30,
            6
        );
    }

    // ============================================
    // BackupCodeInterface Implementation
    // ============================================

    /**
     * Check if a backup code is valid
     */
    public function isBackupCode(string $code): bool
    {
        $codes = $this->backupCodes ?? [];
        
        // Hash the entered code to compare with stored hashes
        $hashedCode = hash('sha256', $code);
        
        foreach ($codes as $storedCode) {
            if (hash_equals($storedCode, $hashedCode)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Invalidate a backup code after use
     * @param string $code The plain code or already-hashed code
     */
    public function invalidateBackupCode(string $code): void
    {
        $codes = $this->backupCodes ?? [];
        
        // Check if the code looks like a SHA256 hash (64 characters hex)
        $isAlreadyHashed = preg_match('/^[a-f0-9]{64}$/i', $code);
        
        if ($isAlreadyHashed) {
            // Code is already hashed, use it directly
            $hashToRemove = $code;
        } else {
            // Plain code, hash it
            $hashToRemove = hash('sha256', $code);
        }
        
        // Remove the used code from the array
        $codes = array_filter($codes, function($storedCode) use ($hashToRemove) {
            return !hash_equals($storedCode, $hashToRemove);
        });
        
        $this->backupCodes = array_values($codes);
        
        // Also remove from plain codes for display
        $plainCodes = $this->plainBackupCodes ?? [];
        if (!$isAlreadyHashed) {
            $this->plainBackupCodes = array_values(array_filter($plainCodes, function($plainCode) use ($code) {
                return strtoupper($plainCode) !== strtoupper($code);
            }));
        }
    }

    /**
     * Generate new backup codes
     * Stores PLAIN codes for display, hashed for verification
     */
    public function generateBackupCodes(int $count = 10): array
    {
        $plainCodes = [];
        $hashedCodes = [];
        
        for ($i = 0; $i < $count; $i++) {
            // Generate a random 8-character code with format XXXX-XXXX
            $code = strtoupper(sprintf('%04s-%04s', 
                bin2hex(random_bytes(2)), 
                bin2hex(random_bytes(2))
            ));
            
            $plainCodes[] = $code;
            $hashedCodes[] = hash('sha256', $code);
        }
        
        // Store hashed codes for verification
        $this->backupCodes = $hashedCodes;
        // Store plain codes for display
        $this->plainBackupCodes = $plainCodes;
        
        // Return the plain codes for user to save
        return $plainCodes;
    }
    
    /**
     * Get plain backup codes for display
     */
    public function getPlainBackupCodes(): array
    {
        return $this->plainBackupCodes ?? [];
    }

    /**
     * Get the backup codes (for interface compatibility)
     */
    public function getBackupCodes(): array
    {
        return $this->backupCodes ?? [];
    }

    /**
     * Set the backup codes (for interface compatibility)
     */
    public function setBackupCodes(array $codes): void
    {
        $this->backupCodes = $codes;
    }

    // ============================================
    // Custom Trusted Devices Methods
    // ============================================

    /**
     * Get the trusted device identifier
     */
    public function getTrustedDeviceIdentifier(): string
    {
        return $this->uuid;
    }

    /**
     * Get trusted devices list
     */
    public function getTrustedDevices(): array
    {
        return $this->trustedDevices ?? [];
    }

    /**
     * Add a trusted device
     */
    public function addTrustedDevice(string $deviceToken, \DateTimeInterface $expiresAt): void
    {
        $devices = $this->getTrustedDevices();
        $devices[] = [
            'token' => $deviceToken,
            'expiresAt' => $expiresAt->format('Y-m-d H:i:s'),
            'createdAt' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];
        
        $this->trustedDevices = $devices;
    }

    /**
     * Remove a trusted device
     */
    public function removeTrustedDevice(string $deviceToken): void
    {
        $devices = $this->getTrustedDevices();
        $devices = array_filter($devices, function($device) use ($deviceToken) {
            return $device['token'] !== $deviceToken;
        });
        
        $this->trustedDevices = array_values($devices);
    }

    /**
     * Check if a device token is trusted
     */
    public function isTrustedDevice(string $deviceToken): bool
    {
        $devices = $this->getTrustedDevices();
        
        foreach ($devices as $device) {
            if ($device['token'] === $deviceToken) {
                // Check if not expired
                $expiresAt = new \DateTime($device['expiresAt']);
                if ($expiresAt > new \DateTime()) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Clean up expired trusted devices
     */
    public function cleanupExpiredTrustedDevices(): void
    {
        $devices = $this->getTrustedDevices();
        $now = new \DateTime();
        
        $devices = array_filter($devices, function($device) use ($now) {
            $expiresAt = new \DateTime($device['expiresAt']);
            return $expiresAt > $now;
        });
        
        $this->trustedDevices = array_values($devices);
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // Symfony Security Interface Methods
    public function getRoles(): array
    {
        return [$this->getDiscriminatorValue() ?? 'ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // Clear any temporary sensitive data
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    abstract public function getDiscriminatorValue(): string;

    /**
     * @return Collection<int, Goal>
     */
    public function getGoals(): Collection
    {
        return $this->goals;
    }

    public function addGoal(Goal $goal): static
    {
        if (!$this->goals->contains($goal)) {
            $this->goals->add($goal);
            $goal->setPatient($this);
        }

        return $this;
    }

    public function removeGoal(Goal $goal): static
    {
        if ($this->goals->removeElement($goal)) {
            // set the owning side to null (unless already changed)
            if ($goal->getPatient() === $this) {
                $goal->setPatient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Exercises>
     */
    public function getExercises(): Collection
    {
        return $this->exercises;
    }

    public function addExercise(Exercises $exercise): static
    {
        if (!$this->exercises->contains($exercise)) {
            $this->exercises->add($exercise);
            $exercise->setUser($this);
        }

        return $this;
    }

    public function removeExercise(Exercises $exercise): static
    {
        if ($this->exercises->removeElement($exercise)) {
            // set the owning side to null (unless already changed)
            if ($exercise->getUser() === $this) {
                $exercise->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, DailyPlan>
     */
    public function getDailyPlans(): Collection
    {
        return $this->dailyPlans;
    }

    public function addDailyPlan(DailyPlan $dailyPlan): static
    {
        if (!$this->dailyPlans->contains($dailyPlan)) {
            $this->dailyPlans->add($dailyPlan);
            $dailyPlan->setCoach($this);
        }

        return $this;
    }

    public function removeDailyPlan(DailyPlan $dailyPlan): static
    {
        if ($this->dailyPlans->removeElement($dailyPlan)) {
            // set the owning side to null (unless already changed)
            if ($dailyPlan->getCoach() === $this) {
                $dailyPlan->setCoach(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getCoach(): Collection
    {
        return $this->coach;
    }

    public function addCoach(Conversation $coach): static
    {
        if (!$this->coach->contains($coach)) {
            $this->coach->add($coach);
            $coach->setPatient($this);
        }

        return $this;
    }

    public function removeCoach(Conversation $coach): static
    {
        if ($this->coach->removeElement($coach)) {
            // set the owning side to null (unless already changed)
            if ($coach->getPatient() === $this) {
                $coach->setPatient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Conversation>
     */
    public function getConversations(): Collection
    {
        return $this->conversations;
    }

    public function addConversation(Conversation $conversation): static
    {
        if (!$this->conversations->contains($conversation)) {
            $this->conversations->add($conversation);
            $conversation->setPatient($this);
        }

        return $this;
    }

    public function removeConversation(Conversation $conversation): static
    {
        if ($this->conversations->removeElement($conversation)) {
            // set the owning side to null (unless already changed)
            if ($conversation->getPatient() === $this) {
                $conversation->setPatient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setSender($this);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        if ($this->messages->removeElement($message)) {
            // set the owning side to null (unless already changed)
            if ($message->getSender() === $this) {
                $message->setSender(null);
            }
        }

        return $this;
    }
}
