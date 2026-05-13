<?php

namespace App\Service;

use App\Entity\ProfessionalVerification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Smalot\PdfParser\Parser as PdfParser;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class DiplomaVerificationService
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $params;
    private SluggerInterface $slugger;
    private string $uploadDir;
    
    // OCR.space API key
    private const OCR_API_KEY = 'K85368597788957';
    private const OCR_API_URL = 'https://api.ocr.space/parse/image';

    // Scoring weights
    private const WEIGHT_NAME_MATCH = 30;
    private const WEIGHT_SPECIALTY_MATCH = 25;
    private const WEIGHT_LICENSE_FORMAT = 20;
    private const WEIGHT_EXTRACTED_INFO = 15;
    private const WEIGHT_FORGERY_CHECK = 10;

    // Medical specialties mapping (English to French and vice versa)
    private const SPECIALTY_MAPPING = [
        // Doctor specialties
        'CARDIOLOGY' => ['cardiologie', 'cardiology', 'cardio'],
        'DERMATOLOGY' => ['dermatologie', 'dermatology', 'dermato'],
        'ENDOCRINOLOGY' => ['endocrinologie', 'endocrinology', 'endo'],
        'GASTROENTEROLOGY' => ['gastroenterologie', 'gastroenterology', 'gastro'],
        'NEUROLOGY' => ['neurologie', 'neurology', 'neuro'],
        'PSYCHIATRY' => ['psychiatrie', 'psychiatry', 'psy'],
        'PHYSIOTHERAPY' => ['physiotherapie', 'physiotherapy', 'physio', 'kinésithérapie', 'kinesitherapie'],
        'PEDIATRICS' => ['pediatrie', 'pediatrics', 'pediatrie', 'enfant'],
        'GYNECOLOGY' => ['gynecologie', 'gynecology', 'gyneco', 'gyneco'],
        'OPHTHALMOLOGY' => ['ophtalmologie', 'ophthalmology', 'ophta'],
        'OTHER' => ['autre', 'other'],
        
        // Nutritionist specialties
        'CLINICAL_NUTRITION' => ['clinical nutrition', 'nutrition clinique', 'nutrition clinique'],
        'SPORTS_NUTRITION' => ['sports nutrition', 'nutrition sportive', 'nutrition du sport'],
        'PEDIATRIC_NUTRITION' => ['pediatric nutrition', 'nutrition pédiatrique', 'nutrition infantile'],
        'GERIATRIC_NUTRITION' => ['geriatric nutrition', 'nutrition gériatrique', 'nutrition personnes âgées'],
        'WEIGHT_MANAGEMENT' => ['weight management', 'gestion du poids', 'contrôle pondéral'],
        'DIABETES_NUTRITION' => ['diabetes nutrition', 'nutrition diabète', 'diabète'],
        'DIGESTIVE_HEALTH' => ['digestive health', 'santé digestive', 'digestion'],
        'FOOD_ALLERGIES' => ['food allergies', 'allergies alimentaires', 'intolérances alimentaires'],
        'PLANT_BASED_NUTRITION' => ['plant based nutrition', 'nutrition végane', 'nutrition végétalienne', 'végétarisme'],
        
        // Coach specialties
        'FITNESS_COACHING' => ['fitness coaching', 'coaching fitness', 'coaching sportif'],
        'PERSONAL_TRAINING' => ['personal training', 'entraînement personnel', 'coach personnel'],
        'SPORTS_COACHING' => ['sports coaching', 'entraînement sportif'],
        'WELLNESS_COACHING' => ['wellness coaching', 'coaching bien-être'],
        'REHABILITATION_COACHING' => ['rehabilitation coaching', 'coaching rééducation', 'réadaptation'],
        'NUTRITION_COACHING' => ['nutrition coaching', 'coaching nutrition'],
        'WEIGHT_LOSS_SPECIALIST' => ['weight loss specialist', 'spécialiste perte de poids'],
        'STRENGTH_TRAINING' => ['strength training', 'entraînement force', 'musculation'],
        'CARDIO_TRAINING' => ['cardio training', 'entraînement cardio', 'cardio'],
    ];

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $params,
        SluggerInterface $slugger
    ) {
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->slugger = $slugger;
        $this->uploadDir = $this->params->get('kernel.project_dir') . '/public/uploads/diplomas';
    }

    /**
     * Get User entity from UUID
     */
    private function getUserFromUuid(string $uuid): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['uuid' => $uuid]);
    }

    /**
     * Process a verification request
     */
    public function processVerification(ProfessionalVerification $verification): ProfessionalVerification
    {
        $verification->setStatus(ProfessionalVerification::STATUS_PROCESSING);
        $this->entityManager->flush();

        try {
            // 1. Extract text from diploma
            $extractedText = $this->extractTextFromDiploma($verification->getDiplomaPath());
            
            // 2. Parse extracted text to get structured data
            $extractedData = $this->parseExtractedText($extractedText);
            $verification->setExtractedData($extractedData);

            // 3. Get user data from UUID
            $user = $this->getUserFromUuid($verification->getProfessionalUuid());
            if (!$user) {
                throw new \Exception('User not found with UUID: ' . $verification->getProfessionalUuid());
            }
            
            $userData = $this->getUserFormData($user);

            // 4. Calculate matching scores
            $validationDetails = $this->calculateMatchingScores($userData, $extractedData, $verification);
            $verification->setValidationDetails($validationDetails);

            // 5. Check for forgery indicators
            $forgeryIndicators = $this->checkForgeryIndicators($verification->getDiplomaPath());
            $verification->setForgeryIndicators($forgeryIndicators);

            // 6. Calculate final confidence score
            $confidenceScore = $this->calculateConfidenceScore($validationDetails, $forgeryIndicators);
            $verification->setConfidenceScore($confidenceScore);

            // 7. Make automatic decision
            $this->makeDecision($verification, $user);

            return $verification;

        } catch (\Exception $e) {
            // If processing fails, set to manual review
            $verification->setStatus(ProfessionalVerification::STATUS_MANUAL_REVIEW);
            $verification->setRejectionReason('Erreur lors du traitement automatique: ' . $e->getMessage());
            return $verification;
        }
    }

    /**
     * Extract text from diploma file (PDF or image)
     */
    private function extractTextFromDiploma(?string $filePath): string
    {
        if (!$filePath) {
            return '';
        }

        // Convert URL path to absolute filesystem path if needed
        $absolutePath = $filePath;
        if (strpos($filePath, '/uploads/') === 0) {
            // URL path like /uploads/diplomas/file.pdf -> absolute path
            // Use DIRECTORY_SEPARATOR to handle both Windows and Linux
            $projectDir = $this->params->get('kernel.project_dir');
            $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);
            $absolutePath = $projectDir . DIRECTORY_SEPARATOR . 'public' . $relativePath;
        }

        if (!file_exists($absolutePath)) {
            // Log for debugging
            error_log("[DiplomaVerification] File not found at: " . $absolutePath);
            return '';
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        try {
            if ($extension === 'pdf') {
                return $this->extractTextFromPdf($absolutePath);
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                // Use OCR for images
                return $this->extractTextFromImage($absolutePath);
            }
        } catch (\Exception $e) {
            error_log("[DiplomaVerification] Error extracting text: " . $e->getMessage());
            return '';
        }

        return '';
    }

    /**
     * Extract text from PDF using smalot/pdfparser
     */
    private function extractTextFromPdf(string $filePath): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            $text = $pdf->getText();
            
            // Clean and normalize text
            $text = preg_replace('/\s+/', ' ', $text);
            return trim($text);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Extract text from image using OCR.space API
     */
    private function extractTextFromImage(string $filePath): string
    {
        try {
            // Get absolute path
            $absolutePath = $filePath;
            if (strpos($filePath, '/uploads/') === 0) {
                $projectDir = $this->params->get('kernel.project_dir');
                $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);
                $absolutePath = $projectDir . DIRECTORY_SEPARATOR . 'public' . $relativePath;
            }
            
            if (!file_exists($absolutePath)) {
                error_log("[DiplomaVerification] Image file not found: " . $absolutePath);
                return '';
            }
            
            // Check file size (limit to 1MB for OCR.space free tier)
            $fileSize = filesize($absolutePath);
            if ($fileSize > 1048576) {
                error_log("[DiplomaVerification] Image file too large: " . $fileSize . " bytes");
                return '';
            }
            
            // Prepare the file for upload
            $file = new \CURLFile($absolutePath);
            
            // Send to OCR.space API
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::OCR_API_URL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'apikey' => self::OCR_API_KEY,
                'language' => 'fre',  // French
                'isOverlayRequired' => 'false',
                'file' => $file,
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            // Disable SSL verification for local development
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                error_log("[DiplomaVerification] OCR curl error: " . $curlError);
                return '';
            }
            
            if ($httpCode !== 200) {
                error_log("[DiplomaVerification] OCR API error: HTTP " . $httpCode . " - " . $response);
                return '';
            }
            
            $result = json_decode($response, true);
            
            if (isset($result['ParsedResults']) && is_array($result['ParsedResults'])) {
                $text = '';
                foreach ($result['ParsedResults'] as $parseResult) {
                    if (isset($parseResult['ParsedText'])) {
                        $text .= $parseResult['ParsedText'] . "\n";
                    }
                }
                
                // Clean and normalize text
                $text = preg_replace('/\s+/', ' ', $text);
                error_log("[DiplomaVerification] OCR extracted " . strlen($text) . " characters");
                return trim($text);
            }
            
            if (isset($result['ErrorMessage'])) {
                error_log("[DiplomaVerification] OCR API error: " . implode(', ', $result['ErrorMessage']));
            }
            
            return '';
            
        } catch (\Exception $e) {
            error_log("[DiplomaVerification] OCR exception: " . $e->getMessage());
            return '';
        }
    }

    /**
     * Parse extracted text to get structured data
     */
    private function parseExtractedText(string $text): array
    {
        $data = [
            'names' => [],
            'license_numbers' => [],
            'universities' => [],
            'dates' => [],
            'specialties' => [],
            'raw_text' => $text,
        ];

        if (empty($text)) {
            return $data;
        }

        // ===== 1. EXTRACT NAMES with multiple patterns =====
        $namePatterns = [
            // French patterns
            '/Nom\s*:\s*([A-Za-zÀ-ÿ\s\-]+)/i',
            '/Prénom\s*:\s*([A-Za-zÀ-ÿ\s\-]+)/i',
            '/Prénoms?\s*:\s*([A-Za-zÀ-ÿ\s\-]+)/i',
            '/Nom\s+et\s+prénom\s*:\s*([A-Za-zÀ-ÿ\s\-]+)/i',
            '/Nom\s+de\s+famille\s*:\s*([A-Za-zÀ-ÿ\s\-]+)/i',
            // English patterns
            '/Name\s*:\s*([A-Za-zÀ-ÿ\s\-]+)/i',
            '/Full\s+Name\s*:\s*([A-Za-zÀ-ÿ\s\-]+)/i',
            // Title patterns
            '/Dr\.?\s+([A-Za-zÀ-ÿ\s\-]+)/i',
            '/Docteur\s+([A-Za-zÀ-ÿ\s\-]+)/i',
            '/M\.?\s+([A-Za-zÀ-ÿ\s\-]+)/i',
            '/Mme\.?\s+([A-Za-zÀ-ÿ\s\-]+)/i',
            // Student patterns
            '/Étudiant\s*:\s*([A-Za-zÀ-ÿ\s\-]+)/i',
            '/Candidat\s*:\s*([A-Za-zÀ-ÿ\s\-]+)/i',
        ];

        foreach ($namePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $name = trim($matches[1]);
                if (!empty($name) && strlen($name) > 2) {
                    $data['names'][] = $name;
                }
            }
        }

        // Also extract capitalized name sequences as fallback
        preg_match_all('/\b([A-Z][a-zÀ-ÿ]+(?:\s+[A-Z][a-zÀ-ÿ]+){1,3})\b/', $text, $capitalMatches);
        if (!empty($capitalMatches[1])) {
            foreach ($capitalMatches[1] as $name) {
                $skipWords = ['Monsieur', 'Madame', 'Docteur', 'Professeur', 'Université', 'Faculté', 'Certificat', 'Diplôme', 'République', 'Tunisie'];
                if (!in_array($name, $skipWords) && strlen($name) > 3) {
                    if (!in_array($name, $data['names'])) {
                        $data['names'][] = $name;
                    }
                }
            }
        }

        // ===== 2. EXTRACT LICENSE NUMBERS =====
        $licensePatterns = [
            '/N°?\s*de?\s*licence\s*:\s*([A-Z0-9\-\/]+)/i',
            '/Numéro\s*de?\s*licence\s*:\s*([A-Z0-9\-\/]+)/i',
            '/License\s*(?:number)?\s*:\s*([A-Z0-9\-\/]+)/i',
            '/N°\s*d[\'`]?autorisation\s*:\s*([A-Z0-9\-\/]+)/i',
            '/N°\s*inscription\s*:\s*([A-Z0-9\-\/]+)/i',
            '/\b(MED|N°|No|#)\s*[:.]?\s*(\d{4,15})\b/i',
        ];

        foreach ($licensePatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $license) {
                    $license = trim($license);
                    if (!empty($license) && strlen($license) >= 4) {
                        $data['license_numbers'][] = $license;
                    }
                }
            }
        }

        // ===== 3. EXTRACT DATES =====
        $datePatterns = [
            '/Date\s*:\s*(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})/i',
            '/Date\s+de\s+naissance\s*:\s*(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})/i',
            '/Date\s+d[\'`]?obtention\s*:\s*(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})/i',
            '/Date\s+de\s+délivrance\s*:\s*(\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4})/i',
            '/(\d{1,2}\s+(?:Janvier|Février|Mars|Avril|Mai|Juin|Juillet|Août|Septembre|Octobre|Novembre|Décembre)\s+\d{4})/i',
        ];

        foreach ($datePatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] as $date) {
                    $date = trim($date);
                    if (!empty($date)) {
                        $data['dates'][] = $date;
                    }
                }
            }
        }

        // ===== 4. EXTRACT UNIVERSITIES =====
        $universityPatterns = [
            '/Université\s+([A-Za-zÀ-ÿ\s\-]+)/i',
            '/Université\s+de\s+([A-Za-zÀ-ÿ\s\-]+)/i',
            '/University\s+of\s+([A-Za-zÀ-ÿ\s\-]+)/i',
            '/Faculté\s+de\s+([A-Za-zÀ-ÿ\s\-]+)/i',
            '/Facult[ey]\s+([A-Za-zÀ-ÿ\s\-]+)/i',
            '/École\s+([A-Za-zÀ-ÿ\s\-]+)/i',
        ];

        foreach ($universityPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $university = trim($matches[0]);
                if (!empty($university) && strlen($university) > 5) {
                    $data['universities'][] = $university;
                }
            }
        }

        // Known universities
        $knownUniversities = ['Tunis', 'Tunisie', 'El Manar', 'Carthage', 'Sfax', 'Monastir', 'Sousse', 'Paris', 'Lyon', 'Marseille'];
        foreach ($knownUniversities as $uni) {
            if (stripos($text, $uni) !== false) {
                $data['universities'][] = $uni;
            }
        }

        // ===== 5. EXTRACT SPECIALTIES =====
        foreach (self::SPECIALTY_MAPPING as $english => $frenchVariants) {
            foreach ($frenchVariants as $variant) {
                if (stripos($text, $variant) !== false) {
                    $data['specialties'][] = $english;
                    break;
                }
            }
        }

        // Remove duplicates
        $data['names'] = array_unique($data['names']);
        $data['license_numbers'] = array_unique($data['license_numbers']);
        $data['universities'] = array_unique($data['universities']);
        $data['dates'] = array_unique($data['dates']);
        $data['specialties'] = array_unique($data['specialties']);

        return $data;
    }

    /**
     * Get user data from registration form
     */
    private function getUserFormData(User $user): array
    {
        return [
            'first_name' => strtolower($user->getFirstName() ?? ''),
            'last_name' => strtolower($user->getLastName() ?? ''),
            'full_name' => strtolower(($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? '')),
            'license_number' => strtoupper($user->getLicenseNumber() ?? ''),
            'specialty' => $this->getUserSpecialty($user),
            'experience_years' => $this->getUserExperienceYears($user),
        ];
    }

    /**
     * Get user's specialty
     */
    private function getUserSpecialty(User $user): ?string
    {
        // Check if user has getSpecialite method (Medecin, Coach, Nutritionist)
        if (method_exists($user, 'getSpecialite')) {
            return $user->getSpecialite();
        }
        return null;
    }

    /**
     * Get user's years of experience
     */
    private function getUserExperienceYears(User $user): ?int
    {
        if (method_exists($user, 'getYearsOfExperience')) {
            return $user->getYearsOfExperience();
        }
        return null;
    }

    /**
     * Calculate matching scores between form data and extracted data
     */
    private function calculateMatchingScores(array $userData, array $extractedData, ProfessionalVerification $verification): array
    {
        $scores = [
            'name_match' => 0,
            'specialty_match' => 0,
            'license_format' => 0,
            'extracted_info' => 0,
            'details' => [],
        ];

        // 1. Name matching
        $nameScore = $this->calculateNameMatch($userData, $extractedData);
        $scores['name_match'] = $nameScore['score'];
        $scores['details']['name'] = $nameScore;

        // 2. Specialty matching
        $specialtyScore = $this->calculateSpecialtyMatch($userData, $extractedData);
        $scores['specialty_match'] = $specialtyScore['score'];
        $scores['details']['specialty'] = $specialtyScore;

        // 3. License number format validation and comparison with extracted data
        $licenseScore = $this->validateLicenseFormat(
            $userData['license_number'], 
            $verification->getLicenseNumber() ?? '',
            $extractedData
        );
        $scores['license_format'] = $licenseScore['score'];
        $scores['details']['license'] = $licenseScore;

        // 4. Extracted information quality
        $infoScore = $this->calculateExtractedInfoScore($extractedData);
        $scores['extracted_info'] = $infoScore['score'];
        $scores['details']['extracted'] = $infoScore;

        return $scores;
    }

    /**
     * Calculate name match score
     */
    private function calculateNameMatch(array $userData, array $extractedData): array
    {
        $score = 0;
        $details = [];

        if (empty($extractedData['names'])) {
            return ['score' => 0, 'message' => 'Aucun nom trouvé dans le diplôme', 'matched' => false];
        }

        $extractedNames = array_map('strtolower', $extractedData['names']);
        
        // Check for first name match
        if (in_array($userData['first_name'], $extractedNames)) {
            $score += 50;
            $details['first_name_match'] = true;
        }

        // Check for last name match
        if (in_array($userData['last_name'], $extractedNames)) {
            $score += 50;
            $details['last_name_match'] = true;
        }

        // Check for full name (in any order)
        $fullNameReversed = $userData['last_name'] . ' ' . $userData['first_name'];
        foreach ($extractedNames as $name) {
            if (strpos($name, $userData['first_name']) !== false && 
                strpos($name, $userData['last_name']) !== false) {
                $score = 100;
                $details['full_name_match'] = true;
                break;
            }
        }

        return [
            'score' => $score,
            'message' => $score >= 50 ? 'Nom trouvé dans le diplôme' : 'Nom non trouvé dans le diplôme',
            'matched' => $score > 0,
            'details' => $details,
        ];
    }

    /**
     * Calculate specialty match score
     */
    private function calculateSpecialtyMatch(array $userData, array $extractedData): array
    {
        if (empty($userData['specialty'])) {
            return ['score' => 0, 'message' => 'Aucune spécialité fournie par l\'utilisateur', 'matched' => false];
        }

        if (empty($extractedData['specialties'])) {
            return ['score' => 0, 'message' => 'Aucune spécialité trouvée dans le diplôme', 'matched' => false];
        }

        // Direct match
        $userSpecialty = strtoupper($userData['specialty']);
        if (in_array($userSpecialty, array_map('strtoupper', $extractedData['specialties']))) {
            return [
                'score' => 100,
                'message' => 'Spécialité exacte trouvée',
                'matched' => true,
            ];
        }

        // Check for variant match
        $specialtyVariants = self::SPECIALTY_MAPPING[$userSpecialty] ?? [];
        foreach ($extractedData['specialties'] as $extracted) {
            $extractedVariants = self::SPECIALTY_MAPPING[$extracted] ?? [];
            if (array_intersect($specialtyVariants, $extractedVariants)) {
                return [
                    'score' => 80,
                    'message' => 'Spécialité similaire trouvée',
                    'matched' => true,
                ];
            }
        }

        return [
            'score' => 0,
            'message' => 'Spécialité ne correspond pas',
            'matched' => false,
        ];
    }

    /**
     * Validate license number format and compare with extracted data
     */
    private function validateLicenseFormat(
        ?string $licenseNumber, 
        string $formLicenseNumber,
        array $extractedData = []
    ): array {
        $score = 0;
        $details = [];
        $matched = false;
        $message = '';

        // Get the license number to check (prefer user form data)
        $userLicense = !empty($licenseNumber) ? $licenseNumber : $formLicenseNumber;
        
        if (empty($userLicense) && empty($extractedData['license_numbers'])) {
            return [
                'score' => 0, 
                'message' => 'Numéro de licence manquant', 
                'valid' => false,
                'matched' => false
            ];
        }

        // Normalize user license number
        $userLicenseNormalized = $this->normalizeLicenseNumber($userLicense);

        // ===== 1. CHECK IF LICENSE WAS EXTRACTED FROM DIPLOMA =====
        if (!empty($extractedData['license_numbers'])) {
            $details['extracted_licenses'] = $extractedData['license_numbers'];
            
            // Try to find a match with any extracted license
            foreach ($extractedData['license_numbers'] as $extractedLicense) {
                $extractedNormalized = $this->normalizeLicenseNumber($extractedLicense);
                
                // Exact match
                if ($userLicenseNormalized === $extractedNormalized) {
                    $score = 100;
                    $matched = true;
                    $message = 'Numéro de licence CORRESPOND EXACTEMENT';
                    $details['match_type'] = 'exact';
                    $details['matched_license'] = $extractedLicense;
                    break;
                }
                
                // Partial match (one contains the other)
                if (!empty($userLicenseNormalized) && !empty($extractedNormalized)) {
                    if (strpos($userLicenseNormalized, $extractedNormalized) !== false || 
                        strpos($extractedNormalized, $userLicenseNormalized) !== false) {
                        $score = 80;
                        $matched = true;
                        $message = 'Numéro de licence CORRESPOND PARTIELLEMENT';
                        $details['match_type'] = 'partial';
                        $details['matched_license'] = $extractedLicense;
                        break;
                    }
                }
            }
            
            // If no match found but license was extracted
            if (!$matched) {
                $score = 30;
                $message = 'Numéro de licence trouvé mais ne correspond pas';
                $details['match_type'] = 'mismatch';
                $details['user_license'] = $userLicense;
            }
        } else {
            // ===== 2. NO LICENSE EXTRACTED - JUST VALIDATE FORMAT =====
            $userLicenseNormalized = strtoupper($userLicense);
            
            // Basic format validation
            $isValidFormat = preg_match('/^[A-Z0-9\-\/]{4,25}$/', $userLicenseNormalized);
            
            if ($isValidFormat) {
                $score = 60;
                $message = 'Format valide (pas de comparaison possible)';
                $details['format_valid'] = true;
            } else {
                $score = 20;
                $message = 'Format inattendu';
                $details['format_valid'] = false;
            }
        }

        return [
            'score' => $score,
            'message' => $message,
            'valid' => $score >= 50,
            'matched' => $matched,
            'details' => $details,
        ];
    }

    /**
     * Normalize license number for comparison
     * Removes spaces, dashes, slashes, and converts to uppercase
     */
    private function normalizeLicenseNumber(?string $license): string
    {
        if (empty($license)) {
            return '';
        }
        
        // Remove common separators and normalize
        $normalized = strtoupper($license);
        $normalized = str_replace([' ', '-', '/', '.', ',', ':'], '', $normalized);
        $normalized = preg_replace('/[^A-Z0-9]/', '', $normalized);
        
        return $normalized;
    }

    /**
     * Calculate extracted information quality score
     */
    private function calculateExtractedInfoScore(array $extractedData): array
    {
        $score = 0;
        $details = [];

        // Check how much information was extracted
        if (!empty($extractedData['names'])) {
            $score += 25;
            $details['has_names'] = true;
        }

        if (!empty($extractedData['license_numbers'])) {
            $score += 25;
            $details['has_license'] = true;
        }

        if (!empty($extractedData['dates'])) {
            $score += 25;
            $details['has_dates'] = true;
        }

        if (!empty($extractedData['specialties'])) {
            $score += 25;
            $details['has_specialty'] = true;
        }

        return [
            'score' => $score,
            'message' => $score >= 50 ? 'Informations extraites avec succès' : 'Peu d\'informations extraites',
            'quality' => $score >= 75 ? 'high' : ($score >= 50 ? 'medium' : 'low'),
        ];
    }

    /**
     * Check for forgery indicators
     */
    private function checkForgeryIndicators(string $filePath): array
    {
        $indicators = [
            'suspicious_metadata' => false,
            'recent_modification' => false,
            'inconsistent_timestamps' => false,
            'low_image_quality' => false,
            'warnings' => [],
        ];

        // Convert URL path to absolute filesystem path if needed
        $absolutePath = $filePath;
        if (strpos($filePath, '/uploads/') === 0) {
            $projectDir = $this->params->get('kernel.project_dir');
            $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);
            $absolutePath = $projectDir . DIRECTORY_SEPARATOR . 'public' . $relativePath;
        }

        if (!file_exists($absolutePath)) {
            error_log("[DiplomaVerification] Forgery check - File not found at: " . $absolutePath);
            return $indicators;
        }

        // Check file metadata
        $fileInfo = stat($absolutePath);
        

        // Check file size (too small might indicate compression/low quality)
        $fileSize = filesize($absolutePath);
        if ($fileSize < 10000) { // Less than 10KB
            $indicators['low_image_quality'] = true;
            $indicators['warnings'][] = 'La qualité de l\'image semble faible';
        }

        // Check extension matches actual file type
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        
        // Try to get mime type, fallback to extension-based detection
        $mimeType = null;
        if (function_exists('mime_content_type')) {
            $mimeType = mime_content_type($absolutePath);
        } else {
            // Fallback to extension-based mime type
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ];
            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
        }
        
        $allowedMimes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];

        if (isset($allowedMimes[$extension]) && $mimeType !== $allowedMimes[$extension]) {
            $indicators['suspicious_metadata'] = true;
            $indicators['warnings'][] = 'Le type de fichier ne correspond pas à l\'extension';
        }

        return $indicators;
    }

    /**
     * Calculate final confidence score
     */
    private function calculateConfidenceScore(array $validationDetails, array $forgeryIndicators): int
    {
        $score = 0;

        // Weighted average of scores
        $score += ($validationDetails['name_match'] / 100) * self::WEIGHT_NAME_MATCH;
        $score += ($validationDetails['specialty_match'] / 100) * self::WEIGHT_SPECIALTY_MATCH;
        $score += ($validationDetails['license_format'] / 100) * self::WEIGHT_LICENSE_FORMAT;
        $score += ($validationDetails['extracted_info'] / 100) * self::WEIGHT_EXTRACTED_INFO;

        // Reduce score if forgery indicators found
        if ($forgeryIndicators['suspicious_metadata']) {
            $score -= 20;
        }


        if ($forgeryIndicators['low_image_quality']) {
            $score -= 15;
        }

        // Ensure score is between 0 and 100
        return max(0, min(100, (int) round($score)));
    }

    /**
     * Make automatic decision based on confidence score
     */
    private function makeDecision(ProfessionalVerification $verification, User $user): void
    {
        $score = $verification->getConfidenceScore();

        if ($score >= 80) {
            // Auto-approve
            $verification->setStatus(ProfessionalVerification::STATUS_VERIFIED);
            $verification->setVerifiedAt(new \DateTime());
            
            // Update user's verification status
            if (method_exists($user, 'setVerifiedByAdmin')) {
                $user->setVerifiedByAdmin(true);
                $user->setVerificationDate(new \DateTime());
            }
            
            $verification->setRejectionReason(null);
            
        } elseif ($score >= 60) {
            // Manual review needed
            $verification->setStatus(ProfessionalVerification::STATUS_MANUAL_REVIEW);
            
        } else {
            // Auto-reject
            $verification->setStatus(ProfessionalVerification::STATUS_REJECTED);
            $verification->setVerifiedAt(new \DateTime());
            $verification->setRejectionReason('Score de confiance trop faible (' . $score . '/100). Veuillez fournir des documents supplémentaires.');
        }
    }

    /**
     * Get public URL for diploma file
     */
    public function getDiplomaUrl(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        
        // If already a full URL, return as-is
        if (strpos($path, 'http') === 0) {
            return $path;
        }
        
        // Remove /public prefix if present
        $path = str_replace('/public', '', $path);
        
        return $path;
    }
}
