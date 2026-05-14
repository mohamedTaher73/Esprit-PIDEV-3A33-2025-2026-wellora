<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Health\HealthMetricDTO;
use App\DTO\Health\HealthScoreDTO;
use App\DTO\Health\HealthStatisticsDTO;
use App\DTO\Health\HealthTrendDTO;
use App\DTO\Health\HealthTrendDirection;
use App\DTO\Health\HealthRiskDTO;
use App\DTO\Health\HealthPredictionDTO;
use App\Entity\Healthentry;
use App\Entity\Healthjournal;
use App\Entity\Symptom;
use App\Form\HealthentryType;
use App\Repository\HealthentryRepository;
use App\Repository\HealthjournalRepository;
use App\Repository\SymptomRepository;
use App\Service\Health\HealthAnalyticsService;
use App\Service\Health\HealthRiskEngineService;
use App\Service\Health\HealthTrendService;
use App\Service\Health\HealthPredictionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Consultation;
use App\Entity\Examens;
use App\Entity\Medecin;
use App\Entity\Ordonnance;
use App\Entity\Patient;
use App\Entity\User;
use App\Repository\ConsultationRepository;
use App\Service\AiModelDoctorService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

#[Route('/health')]
final class HealthController extends AbstractController
{
    public function __construct(
        private readonly HealthAnalyticsService $analyticsService,
        private readonly HealthTrendService $trendService,
        private readonly HealthRiskEngineService $riskEngineService,
        private readonly HealthPredictionService $predictionService,
        private AiModelDoctorService $aiModelDoctorService,
        private ConsultationRepository $consultationRepository
    ) {}

    #[Route('/', name: 'app_health_index', methods: ['GET'])]
    public function home(): Response
    {
        // Your logic here
        return new Response('Health Home');
    }

    /**
     * Dashboard principal - Affiche le tableau de bord santé du patient
     */
    #[Route('/dashboard', name: 'health_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_healthentry_index');
    }

    #[Route('/journal', name: 'app_health_journal', methods: ['GET'])]
    public function journal(): Response
    {
        return $this->render('health/journal.html.twig', [
            'controller_name' => 'HealthController',
        ]);
    }

    #[Route('/prediction', name: 'app_health_prediction', methods: ['GET'])]
    public function prediction(
        HealthjournalRepository $journalRepository,
        HealthentryRepository $entryRepository,
        Security $security
    ): Response {
        $user = $security->getUser();
        
        // Get prediction using entries filtered by current user
        $prediction = $this->predictionService->predictGlycemia(null, $user);
        
        // Get total entries count
        $totalEntries = $entryRepository->count([]);
        
        return $this->render('health/prediction.html.twig', [
            'controller_name' => 'HealthController',
            'prediction' => $prediction,
            'totalEntries' => $totalEntries,
        ]);
    }

    #[Route('/dashboard', name: 'app_health_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('health/dashboard.html.twig', [
            'controller_name' => 'HealthController',
        ]);
    }

    // Removed duplicate records() method to avoid redeclaration error
    

    /**
     * Export Data - Exporte les données de santé
     */
    #[Route('/export', name: 'health_export', methods: ['GET'])]
    public function exportData(Request $request): Response
    {
        $format = $request->query->get('format', 'pdf'); // pdf, csv, json
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        // TODO: Récupérer les données réelles depuis la base
        $exportData = [
            'patient' => [
                'id' => 'P001',
                'exportDate' => (new \DateTime())->format('Y-m-d H:i:s'),
                'period' => [
                    'start' => $startDate ?? (new \DateTime('-30 days'))->format('Y-m-d'),
                    'end' => $endDate ?? (new \DateTime())->format('Y-m-d'),
                ],
            ],
            'entries' => [
                [
                    'date' => '2024-01-15',
                    'mood' => 4,
                    'energy' => 8,
                    'sleep' => 7.5,
                    'symptoms' => [],
                ],
                [
                    'date' => '2024-01-14',
                    'mood' => 3,
                    'energy' => 6,
                    'sleep' => 6.0,
                    'symptoms' => ['Fatigue'],
                ],
            ],
            'metrics' => [
                [
                    'date' => '2024-01-15 08:00',
                    'type' => 'heartRate',
                    'value' => 72,
                    'unit' => 'bpm',
                ],
                [
                    'date' => '2024-01-15 08:00',
                    'type' => 'bloodPressure',
                    'value' => '120/80',
                    'unit' => 'mmHg',
                ],
            ],
        ];

        return match ($format) {
            'json' => $this->json([
                'success' => true,
                'data' => $exportData,
            ]),
            'csv' => $this->exportCsv($exportData),
            default => $this->exportPdf($exportData),
        };
    }

    /**
     * Medical Records - Affiche l'historique médical du patient connecté
     */
    #[Route('/records', name: 'health_records', methods: ['GET'])]
    public function records(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est un patient
        if (!$user instanceof Patient) {
            $this->addFlash('error', 'Accès patient requis.');
            return $this->render('health/records.html.twig', [
                'records' => [],
            ]);
        }
        
        // Récupérer les consultations du patient
        $consultations = $em->getRepository(Consultation::class)
            ->createQueryBuilder('c')
            ->where('c.patient = :patient')
            ->setParameter('patient', $user)
            ->orderBy('c.date_consultation', 'DESC')
            ->addOrderBy('c.time_consultation', 'DESC')
            ->getQuery()
            ->getResult();
        
        $records = [];
        foreach ($consultations as $consultation) {
            $medecin = $consultation->getMedecin();
            $doctorName = $medecin ? 'Dr. ' . $medecin->getFirstName() . ' ' . $medecin->getLastName() : '-';
            
            // Construire le résumé à partir des données de consultation
            $summary = $consultation->getReasonForVisit() ?? 'Consultation médicale';
            if ($consultation->getAssessment()) {
                $summary = $consultation->getAssessment();
            }
            
            $records[] = [
                'id' => $consultation->getId(),
                'type' => 'consultation',
                'title' => $consultation->getReasonForVisit() ?? 'Consultation du ' . ($consultation->getDateConsultation() ? $consultation->getDateConsultation()->format('d/m/Y') : '-'),
                'doctor' => $doctorName,
                'date' => $consultation->getDateConsultation() ?? new \DateTime(),
                'summary' => $summary,
                'status' => $consultation->getStatus(),
            ];
        }
        
        // Ajouter les résultats d'examens laboratoire
        $examens = $em->getRepository(Examens::class)
            ->createQueryBuilder('e')
            ->innerJoin('e.consultation', 'c')
            ->where('c.patient = :patient')
            ->setParameter('patient', $user)
            ->orderBy('e.date_examen', 'DESC')
            ->getQuery()
            ->getResult();
        
        foreach ($examens as $examen) {
            $records[] = [
                'id' => $examen->getId(),
                'type' => 'lab_result',
                'title' => $examen->getTypeExamen() ?? 'Examen de laboratoire',
                'doctor' => '-',
                'date' => $examen->getDateExamen() ?? new \DateTime(),
                'summary' => $examen->getResultat() ?? 'Résultat en attente',
                'status' => $examen->getStatus(),
            ];
        }
        
        // Trier tous les enregistrements par date décroissante
        usort($records, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return $this->render('health/records.html.twig', [
            'controller_name' => 'HealthController',
        ]);
    }


    /**
     * Prescriptions - Affiche les ordonnances du patient connecté
     */
    #[Route('/prescriptions', name: 'health_prescriptions', methods: ['GET'])]
    public function prescriptions(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est un patient
        if (!$user instanceof Patient) {
            $this->addFlash('error', 'Accès patient requis.');
            return $this->render('health/prescriptions.html.twig', [
                'prescriptions' => [],
            ]);
        }
        
        // Récupérer les ordonnances du patient via les consultations
        $ordonnances = $em->getRepository(Ordonnance::class)
            ->createQueryBuilder('o')
            ->innerJoin('o.consultation', 'c')
            ->where('c.patient = :patient')
            ->setParameter('patient', $user)
            ->orderBy('o.date_ordonnance', 'DESC')
            ->getQuery()
            ->getResult();
        
        $prescriptions = [];
        foreach ($ordonnances as $ordonnance) {
            $consultation = $ordonnance->getConsultation();
            $consultationStatus = $consultation ? strtolower((string) $consultation->getStatus()) : '';
            $status = in_array($consultationStatus, ['termine', 'completed', 'done'], true) ? 'completed' : 'active';
            
            // Récupérer le nom du médecin
            $doctorName = '-';
            if ($ordonnance->getPrescribedBy()) {
                $doctor = $ordonnance->getPrescribedBy();
                $doctorName = 'Dr. ' . $doctor->getFirstName() . ' ' . $doctor->getLastName();
            } elseif ($consultation && $consultation->getMedecin()) {
                $doctor = $consultation->getMedecin();
                $doctorName = 'Dr. ' . $doctor->getFirstName() . ' ' . $doctor->getLastName();
            }

            $prescriptions[] = [
                'id' => $ordonnance->getId(),
                'medication' => $ordonnance->getMedicament(),
                'dosage' => $ordonnance->getDosage(),
                'duration' => $ordonnance->getDureeTraitement(),
                'doctor' => $doctorName,
                'date' => $ordonnance->getDateOrdonnance(),
                'status' => $status,
            ];
        }


        return $this->render('health/prescriptions.html.twig', [
            'controller_name' => 'HealthController',
            'prescriptions' => $prescriptions,
        ]);
    }


    /**
     * Lab Results - Affiche les résultats de laboratoire
     */
    #[Route('/lab-results', name: 'health_lab_results', methods: ['GET'])]
    public function labResults(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Patient) {
            $this->addFlash('error', 'AccÃ¨s patient requis.');
            return $this->render('health/lab-results.html.twig', [
                'labResults' => [],
            ]);
        }

        $exams = $em->getRepository(Examens::class)->findByPatientUuid($user->getUuid());

        $labResults = [];
        foreach ($exams as $exam) {
            $labResults[] = [
                'id' => $exam->getId(),
                'name' => $exam->getNomExamen() ?: ($exam->getTypeExamen() ?: 'Examen'),
                'date' => $exam->getDateExamen(),
                'status' => $exam->getStatus() ?: 'prescrit',
                'result' => $exam->getResultat() ?: '',
                'resultFile' => $exam->getResultFile(),
                'doctorAnalysis' => $exam->getDoctorAnalysis(),
                'doctorTreatment' => $exam->getDoctorTreatment(),
            ];
        }

        return $this->render('health/lab-results.html.twig', [
            'labResults' => $labResults,
        ]);
    }

    #[Route('/lab-results/{id}/upload', name: 'health_lab_results_upload', methods: ['POST'])]
    public function uploadLabResult(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Patient) {
            $this->addFlash('error', 'AccÃ¨s patient requis.');
            return $this->redirectToRoute('health_lab_results');
        }

        $exam = $em->getRepository(Examens::class)->findOneForPatient($id, $user->getUuid());
        if (!$exam) {
            $this->addFlash('error', 'Examen introuvable');
            return $this->redirectToRoute('health_lab_results');
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('result_pdf');
        if (!$file) {
            $this->addFlash('error', 'Veuillez choisir un fichier PDF.');
            return $this->redirectToRoute('health_lab_results');
        }

        if ($file->getClientOriginalExtension() != 'pdf') {
            $this->addFlash('error', 'Seuls les fichiers PDF sont autoris?s.');
            return $this->redirectToRoute('health_lab_results');
        }

        $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/lab-results';
        if (!is_dir($uploadsDir)) {
            @mkdir($uploadsDir, 0775, true);
        }

        // Remove old file if exists
        $existingFile = $exam->getResultFile();
        if ($existingFile) {
            $existingPath = $this->getParameter('kernel.project_dir') . '/public/' . ltrim($existingFile, '/');
            if (is_file($existingPath)) {
                @unlink($existingPath);
            }
        }

        $filename = 'exam_' . $exam->getId() . '_' . uniqid() . '.pdf';
        $file->move($uploadsDir, $filename);

        $exam->setResultFile('uploads/lab-results/' . $filename);
        if (!$exam->getResultat()) {
            $exam->setResultat('Résultat disponible (PDF).');
        }
        $exam->setStatus('termine');
        $exam->setDateRealisation(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'Résultat PDF enregistré.');
        return $this->redirectToRoute('health_lab_results');
    }

    #[Route('/lab-results/{id}/delete-file', name: 'health_lab_results_delete_file', methods: ['POST'])]
    public function deleteLabResultFile(int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Patient) {
            $this->addFlash('error', 'AccÃ¨s patient requis.');
            return $this->redirectToRoute('health_lab_results');
        }

        $exam = $em->getRepository(Examens::class)->findOneForPatient($id, $user->getUuid());
        if (!$exam) {
            $this->addFlash('error', 'Examen introuvable');
            return $this->redirectToRoute('health_lab_results');
        }

        $existingFile = $exam->getResultFile();
        if ($existingFile) {
            $existingPath = $this->getParameter('kernel.project_dir') . '/public/' . ltrim($existingFile, '/');
            if (is_file($existingPath)) {
                @unlink($existingPath);
            }
        }

        $exam->setResultFile(null);
        $exam->setStatus('prescrit');
        $em->flush();

        $this->addFlash('success', 'Fichier supprimé.');
        return $this->redirectToRoute('health_lab_results');
    }

    #[Route('/doctor/examens/{id}/analysis', name: 'doctor_examens_analysis', methods: ['POST'])]
    public function updateExamAnalysis(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User || $user instanceof Patient) {
            $this->addFlash('error', 'AccÃ¨s mÃ©decin requis.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('doctor_patient_list'));
        }

        $exam = $em->getRepository(Examens::class)->findOneForDoctor($id, $user->getUuid());
        if (!$exam) {
            $this->addFlash('error', 'Examen introuvable ou non autorisÃ©.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('doctor_patient_list'));
        }

        $analysis = trim((string) $request->request->get('doctor_analysis', ''));
        $treatment = trim((string) $request->request->get('doctor_treatment', ''));

        $exam->setDoctorAnalysis($analysis !== '' ? $analysis : null);
        $exam->setDoctorTreatment($treatment !== '' ? $treatment : null);
        $em->flush();

        $this->addFlash('success', 'Analyse et traitement enregistrÃ©s.');
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('doctor_patient_list'));
    }

public function bodyMap(): Response
    {
        // Récupérer les symptômes enregistrés (simulés)
        $recordedSymptoms = [
            [
                'id' => 1,
                'bodyPart' => 'head',
                'symptom' => 'Maux de tête légers',
                'intensity' => 3,
                'date' => new \DateTime('-2 days'),
                'status' => 'resolved',
            ],
            [
                'id' => 2,
                'bodyPart' => 'chest',
                'symptom' => 'Légère oppression',
                'intensity' => 2,
                'date' => new \DateTime('-1 day'),
                'status' => 'active',
            ],
        ];

        // Liste des parties du corps disponibles
        $bodyParts = [
            ['id' => 'head', 'label' => 'Tête', 'icon' => 'brain'],
            ['id' => 'neck', 'label' => 'Cou', 'icon' => 'neck'],
            ['id' => 'chest', 'label' => 'Poitrine', 'icon' => 'lungs'],
            ['id' => 'abdomen', 'label' => 'Abdomen', 'icon' => 'stomach'],
            ['id' => 'back', 'label' => 'Dos', 'icon' => 'back'],
            ['id' => 'leftArm', 'label' => 'Bras gauche', 'icon' => 'arm'],
            ['id' => 'rightArm', 'label' => 'Bras droit', 'icon' => 'arm'],
            ['id' => 'leftLeg', 'label' => 'Jambe gauche', 'icon' => 'leg'],
            ['id' => 'rightLeg', 'label' => 'Jambe droite', 'icon' => 'leg'],
        ];

        // Types de symptômes courants
        $symptomTypes = [
            'Douleur',
            'Engourdissement',
            'Picotements',
            'Brûlure',
            'Crampes',
            'Raideur',
            'Gonflement',
            'Rougeur',
            'Démangeaisons',
            'Autre',
        ];

        return $this->render('health/body_map.html.twig', [
            'recordedSymptoms' => $recordedSymptoms,
            'bodyParts' => $bodyParts,
            'symptomTypes' => $symptomTypes,

        ]);
    }

    #[Route('/symptoms', name: 'app_health_symptoms', methods: ['GET'])]
    public function symptoms(): Response
    {
        return $this->render('health/symptoms.html.twig', [
            'controller_name' => 'HealthController',
        ]);
    }

    #[Route('/billing', name: 'app_health_billing', methods: ['GET'])]
    public function billing(): Response
    {
        return $this->render('health/billing.html.twig', [
            'controller_name' => 'HealthController',
        ]);
    }

    #[Route('/analytics', name: 'app_health_analytics', methods: ['GET', 'POST'])]
    public function analyticsPatient(
        Request $request,
        HealthjournalRepository $journalRepo,
        Security $security
    ): Response {
        // Get selected journal
        $journalId = $request->query->get('journal_id');
        $selectedJournal = $this->resolveSelectedJournal($journalRepo, $journalId, $security);
        
        // Handle case with no data
        if (null === $selectedJournal) {
            return $this->render('health/analytics/patient-view.html.twig', [
                'controller_name' => 'HealthController',
                'has_data' => false,
                'journals' => $journalRepo->findBy(['user' => $security->getUser()]),
                'selected_journal_id' => null,
                'start_date' => null,
                'end_date' => null,
                'start_date_js' => '',
                'end_date_js' => '',
            ]);
        }
        
        // Get analytics data from service
        $analyticsData = $this->analyticsService->getAnalyticsForJournal($selectedJournal);
        
        $metrics = $analyticsData['metrics'];
        $statistics = $analyticsData['statistics'];
        $scores = $analyticsData['scores'];
        
        // Handle case with no entries - still pass date range
        if ($metrics->isEmpty()) {
            $dateRange = $this->parseJournalDateRange($selectedJournal);
            
            return $this->render('health/analytics/patient-view.html.twig', [
                'controller_name' => 'HealthController',
                'has_data' => false,
                'journals' => $journalRepo->findAll(),
                'selected_journal_id' => $selectedJournal->getId(),
                'start_date' => $dateRange['start'],
                'end_date' => $dateRange['end'],
                'start_date_js' => $dateRange['startJs'],
                'end_date_js' => $dateRange['endJs'],
            ]);
        }
        
        // Get trend comparison
        $trend = $this->trendService->compareWithPrevious($selectedJournal);
        
        // Get risk assessment
        $risk = $this->riskEngineService->analyzeRisk($metrics);
        
        // Prepare chart data
        $chartData = $this->prepareChartData($metrics);
        
        // Date range parsing from journal name (kept in controller as it's view-related)
        $dateRange = $this->parseJournalDateRange($selectedJournal);
        
        return $this->render('health/analytics/patient-view.html.twig', [
            'controller_name' => 'HealthController',
            'has_data' => true,
            
            // Chart data
            'glycemic_data' => $chartData['glycemia'],
            'bp_systolic' => $chartData['bpSystolic'],
            'bp_diastolic' => $chartData['bpDiastolic'],
            'sleep_data' => $chartData['sleep'],
            'weight_data' => $chartData['weight'],
            'dates' => $chartData['dates'],
            'symptom_intensity' => $chartData['symptomIntensity'],
            
            // Statistics
            'avg_glycemia' => $statistics->avgGlycemia,
            'min_glycemia' => $statistics->minGlycemia,
            'max_glycemia' => $statistics->maxGlycemia,
            'avg_systolic' => $statistics->avgSystolic,
            'avg_diastolic' => $statistics->avgDiastolic,
            'avg_sleep' => $statistics->avgSleep,
            'current_weight' => $statistics->currentWeight,
            'weight_variation' => $statistics->weightVariation,
            'avg_intensity' => $statistics->avgIntensity,
            'total_symptoms' => $statistics->totalSymptomIntensity,
            
            // Scores
            'glycemic_score' => $scores->glycemicScore,
            'bp_score' => $scores->bloodPressureScore,
            'sleep_score' => $scores->sleepScore,
            'symptom_score' => $scores->symptomScore,
            'weight_score' => $scores->weightScore,
            'global_score' => $scores->globalScore,
            'global_grade' => $scores->globalScoreGrade,
            
            // Trend data
            'has_trend_data' => $trend->hasPreviousData,
            'global_evolution' => $trend->globalEvolutionPercentage,
            'trend_direction' => $trend->globalDirection->value,
            
            // Risk data
            'risk_tier' => $risk->tier->value,
            'risk_score' => $risk->overallRiskScore,
            'risk_summary' => $risk->summary,
            'risk_recommendations' => $risk->recommendations,
            'risk_factors' => array_map(fn($f) => [
                'name' => $f->name,
                'description' => $f->description,
                'severity' => $f->severity,
            ], $risk->riskFactors),
            'requires_attention' => $risk->requiresImmediateAttention,
            
            // Journal info
            'journals' => $journalRepo->findAll(),
            'selected_journal_id' => $selectedJournal->getId(),
            'start_date' => $dateRange['start'],
            'end_date' => $dateRange['end'],
            'start_date_js' => $dateRange['startJs'],
            'end_date_js' => $dateRange['endJs'],
        ]);
    }

    #[Route('/accessible/body-map', name: 'app_health_accessible_body_map', methods: ['GET'])]
    public function bodyMapAccessible(): Response
    {}
    /**
     * Analytics Dashboard - Doctor View (Alias)
     * Alias route for doctor-view template
     */
    #[Route('/analytics/doctor-view', name: 'health_analytics_doctor_view', methods: ['GET'])]
    public function analyticsDoctorView(): Response
    {
        return $this->redirectToRoute('health_analytics_doctor', [], 301);
    }

    /**
     * Analytics Dashboard - Doctor View
     * Affiche le tableau de bord d'analytics pour les médecins avec IA
     */
    #[Route('/analytics/doctor', name: 'health_analytics_doctor', methods: ['GET'])]
    public function analyticsDoctor(): Response
    {
        $doctorId = null;
        $user = $this->getUser();
        if ($user instanceof Medecin) {
            $doctorId = $user->getId();
        } elseif ($user instanceof User && !($user instanceof Patient)) {
            $doctorId = $user->getUuid();
        }

        // Récupérer les données AI pour le tableau de bord médecin
        $aiDashboardData = $this->aiModelDoctorService->getDashboardData($doctorId);
        
        // Si l'API n'est pas disponible OU s'il n'y a pas de doctorId, utiliser des données simulées
        // MAIS garder les données de traitement réelles si disponibles
        $simulatedData = $this->aiModelDoctorService->getSimulatedDashboardData();
        if (!$aiDashboardData['api_available'] || $doctorId === null) {
            // Sauvegarder les données de traitement réelles
            $realTreatmentData = $aiDashboardData['treatment_effectiveness'];
            // Fusionner les données
            $aiDashboardData = array_merge($simulatedData, array_filter($aiDashboardData, fn($v) => $v !== null));
            // Restaurer les données de traitement réelles si elles existent
            if ($realTreatmentData !== null) {
                $aiDashboardData['treatment_effectiveness'] = $realTreatmentData;
            }
        } else {
            // Always use simulated revenue data for testing
            $aiDashboardData['revenue_weekly'] = $simulatedData['revenue_weekly'];
            $aiDashboardData['revenue_monthly'] = $simulatedData['revenue_monthly'];
            // Si l'API est disponible mais certainnes données sont nulles, utiliser les données simulées
            if ($aiDashboardData['profit_predictions'] === null) {
                $aiDashboardData['profit_predictions'] = $simulatedData['profit_predictions'];
            }
            if ($aiDashboardData['profit_alerts'] === null) {
                $aiDashboardData['profit_alerts'] = $simulatedData['profit_alerts'];
            }
            if ($aiDashboardData['treatment_effectiveness'] === null) {
                $aiDashboardData['treatment_effectiveness'] = $simulatedData['treatment_effectiveness'];
            }
        }

        $patients = [];
        $recentAlerts = [];
        $criticalAlerts = 0;
        $todayAppointments = 0;
        $nextAppointment = null;
        $reportsGenerated = 0;

        if ($user instanceof Medecin) {
            $consultations = $this->consultationRepository->findByMedecinOrderedByDateTime($user->getId());
            $byPatient = [];
            foreach ($consultations as $consultation) {
                $patient = $consultation->getPatient();
                if (!$patient) {
                    continue;
                }
                $pid = $patient->getId();
                if (!isset($byPatient[$pid])) {
                    $byPatient[$pid] = [
                        'patient' => $patient,
                        'consultations' => [],
                    ];
                }
                $byPatient[$pid]['consultations'][] = $consultation;
            }

            $now = new \DateTimeImmutable();
            $today = $now->format('Y-m-d');

            foreach ($consultations as $consultation) {
                $date = $consultation->getDateConsultation();
                if ($date && $date->format('Y-m-d') === $today) {
                    $todayAppointments++;
                }

                $time = $consultation->getTimeConsultation();
                if ($date && $time) {
                    $dateTime = \DateTimeImmutable::createFromFormat(
                        'Y-m-d H:i:s',
                        $date->format('Y-m-d') . ' ' . $time->format('H:i:s')
                    );
                    if ($dateTime && $dateTime > $now) {
                        if ($nextAppointment === null || $dateTime < $nextAppointment['time']) {
                            $nextAppointment = [
                                'time' => $dateTime,
                                'label' => $dateTime->format('H:i') . ' - ' . $consultation->getPatient()?->getFirstName(),
                            ];
                        }
                    }
                }
            }

            foreach ($byPatient as $entry) {
                $patient = $entry['patient'];
                $consults = $entry['consultations'];
                $latest = $consults[0] ?? null;
                $previous = $consults[1] ?? null;

                $latestVitals = is_array($latest?->getVitals()) ? $latest->getVitals() : [];
                $latestScore = $this->computeHealthScore($latestVitals);
                $previousScore = $previous ? $this->computeHealthScore(is_array($previous->getVitals()) ? $previous->getVitals() : []) : $latestScore;

                $trend = 'stable';
                $trendLabel = 'Stable';
                if ($latestScore > $previousScore + 2) {
                    $trend = 'improving';
                    $trendLabel = 'Improving';
                } elseif ($latestScore < $previousScore - 2) {
                    $trend = 'declining';
                    $trendLabel = 'Declining';
                }

                $alerts = $latest ? $this->buildAlerts($latest) : [];
                foreach ($alerts as $alert) {
                    if ($alert['severity'] === 'critical') {
                        $criticalAlerts++;
                    }
                    $recentAlerts[] = [
                        'id' => $alert['id'],
                        'severity' => $alert['severity'],
                        'patientName' => $patient->getFirstName() . ' ' . $patient->getLastName(),
                        'message' => $alert['message'],
                        'time' => $this->formatRelativeTime($latest?->getDateConsultation(), $latest?->getTimeConsultation()),
                    ];
                }

                $avatar = $patient->getAvatarUrl();
                if (!$avatar) {
                    $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($patient->getFirstName() . ' ' . $patient->getLastName()) . '&background=00A790&color=fff';
                }

                $patients[] = [
                    'id' => $patient->getId(),
                    'name' => $patient->getFirstName() . ' ' . $patient->getLastName(),
                    'avatar' => $avatar,
                    'healthScore' => $latestScore,
                    'trend' => $trend,
                    'trendLabel' => $trendLabel,
                    'alerts' => $alerts,
                    'lastEntry' => $this->formatRelativeTime($latest?->getDateConsultation(), $latest?->getTimeConsultation()),
                ];
            }
        }

        return $this->render('health/analytics/doctor-view.html.twig', [
            'page_title' => 'Doctor dashboard',
            'patients' => $patients,
            'ai_data' => $aiDashboardData,
            'api_available' => $aiDashboardData['api_available'],
            'doctor_id' => $doctorId,
            'stats' => [
                'criticalAlerts' => $criticalAlerts,
                'todayAppointments' => $todayAppointments,
                'nextAppointment' => $nextAppointment['label'] ?? 'None',
                'reportsGenerated' => $reportsGenerated,
            ],
            'recent_alerts' => array_slice($recentAlerts, 0, 5),
        ]);
    }

    /**
     * Report Generator (Alias)
     * Alias route for report-generator template
     */
    #[Route('/analytics/report-generator', name: 'health_analytics_report_generator', methods: ['GET'])]
    public function analyticsReportGenerator(): Response
    {
        return $this->redirectToRoute('health_analytics_reports', [], 301);
    }

    /**
     * Report Generator
     * Génère des rapports médicaux personnalisés
     */
    #[Route('/analytics/reports', name: 'health_analytics_reports', methods: ['GET'])]
    public function analyticsReports(): Response
    {
        // Liste des patients pour le sélecteur
        $patients = [
            ['id' => 'P001', 'name' => 'Marie Dupont', 'age' => 45, 'gender' => 'F', 'fileNumber' => '2024-001'],
            ['id' => 'P002', 'name' => 'Jean Martin', 'age' => 52, 'gender' => 'M', 'fileNumber' => '2024-002'],
            ['id' => 'P003', 'name' => 'Sophie Bernard', 'age' => 38, 'gender' => 'F', 'fileNumber' => '2024-003'],
        ];

        return $this->render('health/analytics/report-generator.html.twig', [
            'page_title' => 'Générateur de Rapports',
            'patients' => $patients,
        ]);
    }

    /**
     * Get Analytics Data - Récupère les données pour les graphiques d'analytics (AJAX)
     */
    #[Route('/analytics/data', name: 'health_analytics_data', methods: ['GET'])]
    public function getAnalyticsData(Request $request): JsonResponse
    {
        return $this->render('health/accessible/body-map.html.twig', [
            'controller_name' => 'HealthController',
        ]);
    }

    #[Route('/journal/accessible', name: 'app_health_journal_accessible', methods: ['GET', 'POST'])]
    public function journalAccessible(
        Request $request,
        EntityManagerInterface $entityManager,
        HealthjournalRepository $journalRepo,
        HealthentryRepository $entryRepo,
        Security $security
    ): Response {
        $healthentry = new Healthentry();
        // Add empty symptom for form rendering - will be removed before saving if not filled
        $healthentry->addSymptom(new Symptom());
        
        $form = $this->createForm(HealthentryType::class, $healthentry);
        $form->handleRequest($request);
        
        if ($form->isSubmitted()) {
            // Handle "Add Symptom" button
            if ($request->request->has('add_symptom')) {
                $symptom = new Symptom();
                $healthentry->addSymptom($symptom);
                $form = $this->createForm(HealthentryType::class, $healthentry);
                
                return $this->render('health/accessible/journal-entry.html.twig', [
                    'controller_name' => 'HealthController',
                    'form' => $form->createView(),
                ]);
            }
            
            // Manual validation for numeric fields
            $this->validateNumericFields($form);
            
            if ($form->isValid()) {
                $this->processValidEntry($form, $entityManager, $journalRepo, $entryRepo, $request, $security);
            }
        }
        
        return $this->render('health/accessible/journal-entry.html.twig', [
            'controller_name' => 'HealthController',
            'form' => $form->createView(),
        ]);
    }
    /**
     * Get AI Predictions - Récupère les prédictions IA pour les médecins (AJAX)
     */
    #[Route('/analytics/ai/predictions', name: 'health_analytics_ai_predictions', methods: ['GET'])]
    public function getAiPredictions(Request $request): JsonResponse
    {
        $doctorId = $request->query->get('doctor_id');
        if (!is_string($doctorId) || $doctorId === '') {
            $user = $this->getUser();
            if ($user instanceof Medecin) {
                $doctorId = $user->getId();
            } elseif ($user instanceof User && !($user instanceof Patient)) {
                $doctorId = $user->getUuid();
            } else {
                $doctorId = null;
            }
        }
        
        // Récupérer les prédictions
        if (is_string($doctorId) && $doctorId !== '') {
            $predictions = $this->aiModelDoctorService->predictDoctorActivity($doctorId);
            $recommendations = $this->aiModelDoctorService->getDoctorRecommendations($doctorId);
        } else {
            $predictions = $this->aiModelDoctorService->predictAllDoctors();
            $recommendations = null;
        }

        return $this->json([
            'success' => true,
            'api_available' => $this->aiModelDoctorService->isAvailable(),
            'predictions' => $predictions,
            'recommendations' => $recommendations,
            'profit_predictions' => $doctorId ? $this->aiModelDoctorService->getDoctorProfitPredictions($doctorId) : null,
            'revenue_weekly' => $doctorId ? $this->aiModelDoctorService->getDoctorRevenueWeekly($doctorId) : null,
            'revenue_monthly' => $doctorId ? $this->aiModelDoctorService->getDoctorRevenueMonthly($doctorId) : null,
            'profit_alerts' => $doctorId ? $this->aiModelDoctorService->getDoctorProfitAlerts($doctorId) : null,
        ]);
    }


    /**
     * Get AI Status - Vérifie le statut de l'API IA (AJAX)
     */
    #[Route('/analytics/ai/status', name: 'health_analytics_ai_status', methods: ['GET'])]
    public function getAiStatus(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'available' => $this->aiModelDoctorService->isAvailable(),
            'timestamp' => date('c'),
        ]);
    }

    private function computeHealthScore(array $vitals): int
    {
        $score = 100;

        $temp = $vitals['temperature'] ?? null;
        if (is_numeric($temp)) {
            if ($temp >= 39) {
                $score -= 25;
            } elseif ($temp >= 38) {
                $score -= 15;
            }
        }

        $spo2 = $vitals['spo2'] ?? $vitals['oxygenSaturation'] ?? null;
        if (is_numeric($spo2)) {
            if ($spo2 < 88) {
                $score -= 35;
            } elseif ($spo2 < 92) {
                $score -= 25;
            }
        }

        $bp = is_array($vitals['bloodPressure'] ?? null) ? $vitals['bloodPressure'] : [];
        $systolic = $bp['systolic'] ?? $vitals['bloodPressureSystolic'] ?? null;
        $diastolic = $bp['diastolic'] ?? $vitals['bloodPressureDiastolic'] ?? null;
        if (is_numeric($systolic) && is_numeric($diastolic)) {
            if ($systolic >= 160 || $diastolic >= 100) {
                $score -= 20;
            } elseif ($systolic >= 140 || $diastolic >= 90) {
                $score -= 10;
            }
        }

        $pulse = $vitals['pulse'] ?? $vitals['heartRate'] ?? null;
        if (is_numeric($pulse)) {
            if ($pulse > 110 || $pulse < 50) {
                $score -= 10;
            }
        }

        $score = max(0, min(100, $score));
        if ($score === 100 && empty($vitals)) {
            $score = 75;
        }

        return $score;
    }

    private function buildAlerts(\App\Entity\Consultation $consultation): array
    {
        $alerts = [];
        $id = 1;

        if ($consultation->getStatus() === 'emergency' || $consultation->getConsultationType() === 'emergency') {
            $alerts[] = [
                'id' => $id++,
                'severity' => 'critical',
                'message' => 'Emergency consultation',
                'icon' => 'fa-triangle-exclamation',
            ];
        } elseif ($consultation->getStatus() === 'pending') {
            $alerts[] = [
                'id' => $id++,
                'severity' => 'warning',
                'message' => 'Pending consultation',
                'icon' => 'fa-clock',
            ];
        }

        $vitals = is_array($consultation->getVitals()) ? $consultation->getVitals() : [];

        $temp = $vitals['temperature'] ?? null;
        if (is_numeric($temp) && $temp >= 38) {
            $alerts[] = [
                'id' => $id++,
                'severity' => $temp >= 39 ? 'critical' : 'warning',
                'message' => 'Fever detected',
                'icon' => 'fa-temperature-high',
            ];
        }

        $spo2 = $vitals['spo2'] ?? $vitals['oxygenSaturation'] ?? null;
        if (is_numeric($spo2) && $spo2 < 92) {
            $alerts[] = [
                'id' => $id++,
                'severity' => 'critical',
                'message' => 'Low SpO2',
                'icon' => 'fa-lungs',
            ];
        }

        $bp = is_array($vitals['bloodPressure'] ?? null) ? $vitals['bloodPressure'] : [];
        $systolic = $bp['systolic'] ?? $vitals['bloodPressureSystolic'] ?? null;
        $diastolic = $bp['diastolic'] ?? $vitals['bloodPressureDiastolic'] ?? null;
        if (is_numeric($systolic) && is_numeric($diastolic) && ($systolic >= 140 || $diastolic >= 90)) {
            $alerts[] = [
                'id' => $id++,
                'severity' => ($systolic >= 160 || $diastolic >= 100) ? 'critical' : 'warning',
                'message' => 'Elevated blood pressure',
                'icon' => 'fa-heart-pulse',
            ];
        }

        return array_slice($alerts, 0, 3);
    }

    private function formatRelativeTime(?\DateTimeInterface $date, ?\DateTimeInterface $time): string
    {
        if (!$date) {
            return '-';
        }

        $dateTime = $date;
        if ($time) {
            $dateTime = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $date->format('Y-m-d') . ' ' . $time->format('H:i:s')
            ) ?: $date;
        }

        $now = new \DateTimeImmutable();
        $diff = $now->getTimestamp() - $dateTime->getTimestamp();

        if ($diff < 3600) {
            $mins = max(1, (int) floor($diff / 60));
            return $mins . ' min ago';
        }

        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);
            return $hours . 'h ago';
        }

        $days = (int) floor($diff / 86400);
        return $days . 'd ago';
    }

    private function parsePeriodStart(string $period): ?\DateTimeInterface
    {
        $now = new \DateTimeImmutable();
        return match ($period) {
            '7d' => $now->modify('-7 days'),
            '30d' => $now->modify('-30 days'),
            '3m' => $now->modify('-3 months'),
            default => null,
        };
    }

    private function buildPatientReport(array $consultations, string $type, string $period): array
    {
        $latest = $consultations[0] ?? null;
        $patient = $latest?->getPatient();

        $total = count($consultations);
        $avgDuration = $total > 0 ? array_sum(array_map(fn($c) => (int) $c->getDuration(), $consultations)) / $total : 0;
        $statusCounts = [];
        $emergencyCount = 0;
        $vitalSums = ['temperature' => 0.0, 'spo2' => 0.0, 'pulse' => 0.0, 'bp_sys' => 0.0, 'bp_dia' => 0.0];
        $vitalCounts = ['temperature' => 0, 'spo2' => 0, 'pulse' => 0, 'bp' => 0];

        foreach ($consultations as $consultation) {
            $status = $consultation->getStatus() ?? 'unknown';
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

            if ($consultation->getStatus() === 'emergency' || $consultation->getConsultationType() === 'emergency') {
                $emergencyCount++;
            }

            $vitals = is_array($consultation->getVitals()) ? $consultation->getVitals() : [];
            $temp = $vitals['temperature'] ?? null;
            if (is_numeric($temp)) {
                $vitalSums['temperature'] += (float) $temp;
                $vitalCounts['temperature']++;
            }
            $spo2 = $vitals['spo2'] ?? $vitals['oxygenSaturation'] ?? null;
            if (is_numeric($spo2)) {
                $vitalSums['spo2'] += (float) $spo2;
                $vitalCounts['spo2']++;
            }
            $pulse = $vitals['pulse'] ?? $vitals['heartRate'] ?? null;
            if (is_numeric($pulse)) {
                $vitalSums['pulse'] += (float) $pulse;
                $vitalCounts['pulse']++;
            }
            $bp = is_array($vitals['bloodPressure'] ?? null) ? $vitals['bloodPressure'] : [];
            $systolic = $bp['systolic'] ?? $vitals['bloodPressureSystolic'] ?? null;
            $diastolic = $bp['diastolic'] ?? $vitals['bloodPressureDiastolic'] ?? null;
            if (is_numeric($systolic) && is_numeric($diastolic)) {
                $vitalSums['bp_sys'] += (float) $systolic;
                $vitalSums['bp_dia'] += (float) $diastolic;
                $vitalCounts['bp']++;
            }
        }

        $avgVitals = [
            'temperature' => $vitalCounts['temperature'] ? round($vitalSums['temperature'] / $vitalCounts['temperature'], 2) : null,
            'spo2' => $vitalCounts['spo2'] ? round($vitalSums['spo2'] / $vitalCounts['spo2'], 2) : null,
            'pulse' => $vitalCounts['pulse'] ? round($vitalSums['pulse'] / $vitalCounts['pulse'], 2) : null,
            'bloodPressure' => $vitalCounts['bp'] ? [
                'systolic' => round($vitalSums['bp_sys'] / $vitalCounts['bp'], 1),
                'diastolic' => round($vitalSums['bp_dia'] / $vitalCounts['bp'], 1),
            ] : null,
        ];

        return [
            'meta' => [
                'type' => $type,
                'period' => $period,
                'generated_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ],
            'patient' => [
                'id' => $patient?->getId(),
                'name' => $patient ? ($patient->getFirstName() . ' ' . $patient->getLastName()) : null,
            ],
            'summary' => [
                'total_consultations' => $total,
                'average_duration' => round($avgDuration, 2),
                'emergency_count' => $emergencyCount,
                'status_counts' => $statusCounts,
                'last_consultation_date' => $latest?->getDateConsultation()?->format('Y-m-d'),
            ],
            'vitals_average' => $avgVitals,
        ];
    }

    private function renderReportPdf(array $reportData): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $patientName = $reportData['patient']['name'] ?? 'Patient';
        $meta = $reportData['meta'] ?? [];
        $summary = $reportData['summary'] ?? [];
        $vitals = $reportData['vitals_average'] ?? [];

        $html = '<html><head><meta charset="UTF-8"><style>'
            . 'body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }'
            . 'h1 { font-size: 18px; margin-bottom: 8px; }'
            . 'h2 { font-size: 14px; margin-top: 16px; }'
            . '.meta, .section { margin-bottom: 12px; }'
            . 'table { width: 100%; border-collapse: collapse; }'
            . 'th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }'
            . 'th { background: #f5f5f5; }'
            . '</style></head><body>';

        $html .= '<h1>Rapport Medical</h1>';
        $html .= '<div class="meta"><strong>Patient:</strong> ' . htmlspecialchars($patientName) . '<br>';
        $html .= '<strong>Type:</strong> ' . htmlspecialchars((string) ($meta['type'] ?? '')) . '<br>';
        $html .= '<strong>Periode:</strong> ' . htmlspecialchars((string) ($meta['period'] ?? '')) . '<br>';
        $html .= '<strong>Genere le:</strong> ' . htmlspecialchars((string) ($meta['generated_at'] ?? '')) . '</div>';

        $html .= '<div class="section"><h2>Resume</h2><table><tbody>';
        $html .= '<tr><th>Total consultations</th><td>' . htmlspecialchars((string) ($summary['total_consultations'] ?? '0')) . '</td></tr>';
        $html .= '<tr><th>Duree moyenne</th><td>' . htmlspecialchars((string) ($summary['average_duration'] ?? '0')) . '</td></tr>';
        $html .= '<tr><th>Urgences</th><td>' . htmlspecialchars((string) ($summary['emergency_count'] ?? '0')) . '</td></tr>';
        $html .= '<tr><th>Derniere consultation</th><td>' . htmlspecialchars((string) ($summary['last_consultation_date'] ?? '-')) . '</td></tr>';
        $html .= '</tbody></table></div>';

        $html .= '<div class="section"><h2>Signes vitaux (moyenne)</h2><table><tbody>';
        $html .= '<tr><th>Temperature</th><td>' . htmlspecialchars((string) ($vitals['temperature'] ?? '-')) . '</td></tr>';
        $html .= '<tr><th>SpO2</th><td>' . htmlspecialchars((string) ($vitals['spo2'] ?? '-')) . '</td></tr>';
        $html .= '<tr><th>Pulse</th><td>' . htmlspecialchars((string) ($vitals['pulse'] ?? '-')) . '</td></tr>';
        $bp = $vitals['bloodPressure'] ?? null;
        $bpText = '-';
        if (is_array($bp)) {
            $bpText = ($bp['systolic'] ?? '-') . '/' . ($bp['diastolic'] ?? '-');
        }
        $html .= '<tr><th>Tension</th><td>' . htmlspecialchars((string) $bpText) . '</td></tr>';
        $html .= '</tbody></table></div>';

        $html .= '</body></html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function sanitizeFilename(string $name): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
        if ($value === false) {
            $value = $name;
        }
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);
        $value = trim($value ?? '', '-');
        return $value !== '' ? $value : 'patient';
    }

    /**
     * Generate Report - Génère un rapport médical (AJAX)
     */
    #[Route('/analytics/generate-report', name: 'health_generate_report', methods: ['POST'])]
    public function generateReport(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['patient_id'])) {
            return $this->json([
                'success' => false,
                'message' => 'Données invalides',
            ], 400);
        }

        $user = $this->getUser();
        if (!$user instanceof Medecin) {
            return $this->json([
                'success' => false,
                'message' => 'Accès refusé',
            ], 403);
        }

        $patientId = (string) $data['patient_id'];
        $reportType = (string) ($data['report_type'] ?? 'summary');
        $reportPeriod = (string) ($data['report_period'] ?? '30d');
        $since = $this->parsePeriodStart($reportPeriod);

        $consultations = $this->consultationRepository
            ->findByMedecinAndPatientOrderedByDateTime($user->getId(), $patientId, $since);

        if (count($consultations) === 0) {
            return $this->json([
                'success' => false,
                'message' => 'Aucune consultation trouvée pour ce patient',
            ], 404);
        }

        $reportId = uniqid('RPT-');
        $reportData = $this->buildPatientReport($consultations, $reportType, $reportPeriod);

        $reportFormat = (string) ($data['report_format'] ?? 'pdf');

        if ($reportFormat === 'pdf') {
            $pdfContent = $this->renderReportPdf($reportData);
            $patientName = (string) ($reportData['patient']['name'] ?? 'patient');
            $slug = $this->sanitizeFilename($patientName);
            $date = (new \DateTimeImmutable())->format('Y-m-d');
            $filename = 'rapport-' . $slug . '-' . $date . '.pdf';
            $response = new Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            return $response;
        }

        return $this->json([
            'success' => true,
            'message' => 'Rapport g?n?r? avec succ?s',
            'report_id' => $reportId,
            'report' => $reportData,
            'filename' => 'rapport-' . $reportId . '.json',
        ]);
    }

    #[Route('/accessible/journal-entry', name: 'app_health_accessible_journal_entry', methods: ['GET'])]
    public function accessibleJournalEntryRedirect(): Response
    {
        return $this->redirectToRoute('app_health_journal_accessible', [], 301);
    }

    // ============================================
    // PRIVATE HELPER METHODS
    // ============================================
    
    private function resolveSelectedJournal(
        HealthjournalRepository $journalRepo,
        ?string $journalId,
        Security $security
    ): ?Healthjournal {
        $user = $security->getUser();
        
        if (!$user) {
            return null;
        }
        
        // If specific journal requested, verify it belongs to user
        if ($journalId) {
            $journal = $journalRepo->find((int) $journalId);
            // Verify journal belongs to current user
            if ($journal && $journal->getUser() === $user) {
                return $journal;
            }
            return null;
        }
        
        // Get user's journals and return first one
        $journals = $journalRepo->findBy(['user' => $user], ['datedebut' => 'DESC']);
        
        return !empty($journals) ? $journals[0] : null;
    }
    
    /**
     * @param \App\DTO\Health\HealthMetricDTO $metrics
     */
    private function prepareChartData($metrics): array
    {
        // Create new arrays for template to avoid readonly issues
        $glycemia = [];
        $bpSystolic = [];
        $bpDiastolic = [];
        $sleep = [];
        $weight = [];
        $dates = [];
        $symptomIntensity = [];
        
        foreach ($metrics->glycemia as $v) {
            $glycemia[] = $v;
        }
        foreach ($metrics->bloodPressureSystolic as $v) {
            $bpSystolic[] = $v;
        }
        foreach ($metrics->bloodPressureDiastolic as $v) {
            $bpDiastolic[] = $v;
        }
        foreach ($metrics->sleep as $v) {
            $sleep[] = $v;
        }
        foreach ($metrics->weight as $v) {
            $weight[] = $v;
        }
        foreach ($metrics->symptomIntensity as $v) {
            $symptomIntensity[] = $v;
        }
        foreach ($metrics->dates as $d) {
            $dates[] = $d instanceof \DateTimeInterface 
                ? $d->format('d/m/Y') 
                : (is_object($d) ? $d->format('d/m/Y') : '');
        }
        
        return [
            'glycemia' => $glycemia,
            'bpSystolic' => $bpSystolic,
            'bpDiastolic' => $bpDiastolic,
            'sleep' => $sleep,
            'weight' => $weight,
            'dates' => $dates,
            'symptomIntensity' => $symptomIntensity,
        ];
    }
    
    private function parseJournalDateRange(Healthjournal $journal): array
    {
        $journalName = $journal->getName() ?? '';
        $datedebut = $journal->getDatedebut();
        $datefin = $journal->getDatefin();
        
        // Try to extract month/year from journal name
        $extracted = $this->extractMonthYearFromName($journalName);
        
        if (null !== $extracted) {
            $startDate = $extracted['start'];
            $endDate = $extracted['end'];
        } elseif (null !== $datedebut && null !== $datefin) {
            $startDate = $datedebut;
            $endDate = $datefin;
        } else {
            $startDate = new \DateTime();
            $endDate = new \DateTime();
        }
        
        return [
            'start' => $startDate->format('d/m/Y'),
            'end' => $endDate->format('d/m/Y'),
            'startJs' => $startDate->format('Y-m-d'),
            'endJs' => $endDate->format('Y-m-d'),
        ];
    }
    
    private function extractMonthYearFromName(string $name): ?array
    {
        $frenchMonths = [
            'janvier' => 1, 'février' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'août' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'décembre' => 12,
        ];
        
        $englishMonths = [
            'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
            'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
            'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
        ];
        
        $nameLower = strtolower($name);
        $month = null;
        
        // Check French months
        foreach ($frenchMonths as $monthName => $monthNum) {
            if (str_contains($nameLower, $monthName)) {
                $month = $monthNum;
                break;
            }
        }
        
        // Check English months if not found
        if (null === $month) {
            foreach ($englishMonths as $monthName => $monthNum) {
                if (str_contains($nameLower, $monthName)) {
                    $month = $monthNum;
                    break;
                }
            }
        }
        
        // Extract year
        $year = (int) date('Y');
        if (preg_match('/\b(19|20)\d{2}\b/', $name, $matches)) {
            $year = (int) $matches[0];
        }
        
        if (null === $month) {
            return null;
        }
        
        $startDate = new \DateTime(sprintf('%d-%02d-01', $year, $month));
        $endDate = (clone $startDate)->modify('last day of this month');
        
        return ['start' => $startDate, 'end' => $endDate];
    }
    
    private function validateNumericFields($form): void
    {
        $date = $form->get('date')->getData();
        if (null !== $date) {
            $today = new \DateTime('today');
            if ($date > $today) {
                $form->get('date')->addError(new \Symfony\Component\Form\FormError(
                    'La date ne peut pas être dans le futur'
                ));
            }
        }
        
        $poids = $form->get('poids')->getData();
        if (null !== $poids && $poids !== '' && ($poids < 30 || $poids > 200)) {
            $form->get('poids')->addError(new \Symfony\Component\Form\FormError(
                'Le poids doit être compris entre 30 et 200 kg'
            ));
        }
        
        $glycemie = $form->get('glycemie')->getData();
        if (null !== $glycemie && $glycemie !== '' && ($glycemie < 0.5 || $glycemie > 3)) {
            $form->get('glycemie')->addError(new \Symfony\Component\Form\FormError(
                'La glycémie doit être comprise entre 0.5 et 3 g/l'
            ));
        }
        
        $tension = $form->get('tension')->getData();
        if (null !== $tension && $tension !== '') {
            $tensionValue = (float) $tension;
            if ($tensionValue < 40 || $tensionValue > 120) {
                $form->get('tension')->addError(new \Symfony\Component\Form\FormError(
                    'La tension doit être comprise entre 40 et 120 mmHg'
                ));
            }
        }
        
        $sommeil = $form->get('sommeil')->getData();
        if (null !== $sommeil && $sommeil !== '' && ($sommeil < 0 || $sommeil > 12)) {
            $form->get('sommeil')->addError(new \Symfony\Component\Form\FormError(
                'Le sommeil doit être compris entre 0 et 12 heures'
            ));
        }
        // Removed invalid return statement from void function
    }

    /**
     * Doctor Interface - Patient List
     * Affiche la liste des patients acceptés pour le médecin connecté
     */
    #[Route('/doctor/patients', name: 'doctor_patient_list', methods: ['GET'])]
    public function doctorPatientList(ConsultationRepository $consultationRepository): Response
    {
        // Récupérer le médecin actuellement connecté
        $user = $this->getUser();
        
        // Récupérer uniquement les consultations acceptées pour ce médecin
        $consultations = $consultationRepository->findAcceptedByMedecin($user->getUuid());
        
        return $this->render('doctor/patient-list.html.twig', [
            'page_title' => 'Liste des Patients',
            'consultations' => $consultations,
        ]);
    }

    /**
     * Doctor Interface - Patient Chart
     * Affiche le dossier médical complet d'une consultation avec ses symptômes et traitements
     */
    #[Route('/doctor/patient/{id}/chart', name: 'doctor_patient_chart', methods: ['GET'])]
    public function doctorPatientChart(int $id, EntityManagerInterface $em): Response
    {
        // Récupérer la consultation par ID
        $consultation = $em->getRepository(Consultation::class)->find($id);
        
        if (!$consultation) {
            return $this->render('doctor/patient-chart.html.twig', [
                'page_title' => 'Dossier Médical',
                'consultation' => null,
            ]);
        }
        
        // Récupérer le patient depuis la consultation
        $patient = $consultation->getPatient();
        
        // Préparer les données de la consultation actuelle
        $currentConsultation = [
            'id' => $consultation->getId(),
            'date' => $consultation->getDateConsultation() ? $consultation->getDateConsultation()->format('d/m/Y') : 'N/A',
            'time' => $consultation->getTimeConsultation() ? $consultation->getTimeConsultation()->format('H:i') : '',
            'reasonForVisit' => $consultation->getReasonForVisit(),
            'symptomsDescription' => $consultation->getSymptomsDescription(),
            'diagnoses' => $consultation->getDiagnoses() ?? [],
            'assessment' => $consultation->getAssessment(),
            'plan' => $consultation->getPlan(),
            'notes' => $consultation->getNotes(),
            'status' => $consultation->getStatus(),
            'soapNotes' => $consultation->getSoapNotes() ?? [],
            'appointmentMode' => $consultation->getAppointmentMode(),
            'consultationType' => $consultation->getConsultationType(),
            'duration' => $consultation->getDuration(),
            'location' => $consultation->getLocation(),
        ];
        
        // Préparer les données du patient
        $patientData = null;
        $allVitals = [];
        $allMedications = [];
        $allExamens = [];
        $timelineData = [];
        $allConsultationsData = [];
        
        if ($patient) {
            $patientData = [
                'id' => $patient->getUuid(),
                'name' => trim(($patient->getFirstName() ?? '') . ' ' . ($patient->getLastName() ?? '')),
                'firstName' => $patient->getFirstName(),
                'lastName' => $patient->getLastName(),
                'email' => $patient->getEmail(),
                'phone' => $patient->getPhone(),
                'age' => $patient->getBirthdate() ? $patient->getBirthdate()->diff(new \DateTime())->y : null,
                'gender' => 'M', // Default
                'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode(trim(($patient->getFirstName() ?? '') . ' ' . ($patient->getLastName() ?? ''))) . '&background=00A790&color=fff',
                'birthDate' => $patient->getBirthdate() ? $patient->getBirthdate()->format('d/m/Y') : '--',
            ];
            
            // Récupérer toutes les consultations du patient
            $allConsultations = $em->getRepository(Consultation::class)->findBy(
                ['patient' => $patient],
                ['date_consultation' => 'DESC']
            );
            
            // Agréger toutes les données de toutes les consultations
            foreach ($allConsultations as $cons) {
                // Build consultations list for sidebar
                $allConsultationsData[] = [
                    'id' => $cons->getId(),
                    'date' => $cons->getDateConsultation() ? $cons->getDateConsultation()->format('d/m/Y') : 'N/A',
                    'time' => $cons->getTimeConsultation() ? $cons->getTimeConsultation()->format('H:i') : '',
                    'reasonForVisit' => $cons->getReasonForVisit(),
                    'status' => $cons->getStatus(),
                    'isCurrent' => $cons->getId() === $consultation->getId(),
                ];
                
                // Vitals
                $vitals = $cons->getVitals();
                if (!empty($vitals)) {
                    // Handle both flat and nested blood pressure structures
                    $bpS = $vitals['bloodPressureSystolic'] ?? null;
                    $bpD = $vitals['bloodPressureDiastolic'] ?? null;
                    
                    // Check for nested bloodPressure object
                    if (isset($vitals['bloodPressure']) && is_array($vitals['bloodPressure'])) {
                        $bpS = $bpS ?? $vitals['bloodPressure']['systolic'] ?? null;
                        $bpD = $bpD ?? $vitals['bloodPressure']['diastolic'] ?? null;
                    }
                    
                    $bloodPressure = null;
                    if ($bpS && $bpD) {
                        $bloodPressure = $bpS . '/' . $bpD;
                    } elseif (isset($vitals['bloodPressure']) && is_string($vitals['bloodPressure'])) {
                        $bloodPressure = $vitals['bloodPressure'];
                    }
                    
                    $allVitals[] = [
                        'date' => $cons->getDateConsultation() ? $cons->getDateConsultation()->format('d/m/Y') : 'N/A',
                        'time' => $cons->getTimeConsultation() ? $cons->getTimeConsultation()->format('H:i') : '',
                        'bloodPressure' => $bloodPressure ?? '--',
                        'heartRate' => $vitals['heartRate'] ?? $vitals['pulse'] ?? null,
                        'temperature' => $vitals['temperature'] ?? null,
                        'weight' => $vitals['weight'] ?? null,
                        'height' => $vitals['height'] ?? null,
                        'spo2' => $vitals['oxygenSaturation'] ?? $vitals['spo2'] ?? null,
                        'consultationId' => $cons->getId(),
                    ];
                }
                
                // Medications (ordonnances)
                $ordonnances = $em->getRepository(Ordonnance::class)->findBy(['consultation' => $cons]);
                foreach ($ordonnances as $ord) {
                    $allMedications[] = [
                        'id' => $ord->getId(),
                        'name' => $ord->getMedicament(),
                        'dosage' => $ord->getDosage(),
                        'frequency' => $ord->getFrequency(),
                        'instructions' => $ord->getInstructions(),
                        'date' => $ord->getDateOrdonnance() ? $ord->getDateOrdonnance()->format('d/m/Y') : '--',
                        'consultationId' => $cons->getId(),
                    ];
                }
                
                // Examens
                $examens = $em->getRepository(Examens::class)->findBy(['consultation' => $cons]);
                foreach ($examens as $exam) {
                    $allExamens[] = [
                        'id' => $exam->getId(),
                        'name' => $exam->getNomExamen(),
                        'type' => $exam->getTypeExamen(),
                        'result' => $exam->getResultat(),
                        'date' => $exam->getDateExamen() ? $exam->getDateExamen()->format('d/m/Y') : '--',
                        'consultationId' => $cons->getId(),
                        'status' => $exam->getStatus(),
                        'resultFile' => $exam->getResultFile(),
                        'doctorAnalysis' => $exam->getDoctorAnalysis(),
                        'doctorTreatment' => $exam->getDoctorTreatment(),
                    ];
                }
                
                // Timeline
                $timelineData[] = [
                    'id' => $cons->getId(),
                    'type' => 'consultation',
                    'typeLabel' => 'Consultation',
                    'title' => $cons->getReasonForVisit() ?? 'Consultation',
                    'description' => $cons->getSymptomsDescription() ?? $cons->getNotes() ?? 'Pas de description',
                    'date' => $cons->getDateConsultation() ? $cons->getDateConsultation()->format('d/m/Y') : 'N/A',
                    'status' => $cons->getStatus(),
                ];
            }
        } else {
            // Fallback: utiliser les données de la consultation unique
            $patientData = [
                'id' => $consultation->getId(),
                'name' => $consultation->getReasonForVisit() ?? 'Patient',
                'firstName' => null,
                'lastName' => null,
                'email' => '--',
                'phone' => '--',
                'age' => null,
                'gender' => 'M',
                'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($consultation->getReasonForVisit() ?? 'Patient') . '&background=00A790&color=fff',
                'birthDate' => '--',
            ];
            
            // Vitals from single consultation
            $vitals = $consultation->getVitals();
            if (!empty($vitals)) {
                $bpS = $vitals['bloodPressureSystolic'] ?? null;
                $bpD = $vitals['bloodPressureDiastolic'] ?? null;
                
                if (isset($vitals['bloodPressure']) && is_array($vitals['bloodPressure'])) {
                    $bpS = $bpS ?? $vitals['bloodPressure']['systolic'] ?? null;
                    $bpD = $bpD ?? $vitals['bloodPressure']['diastolic'] ?? null;
                }
                
                $bloodPressure = null;
                if ($bpS && $bpD) {
                    $bloodPressure = $bpS . '/' . $bpD;
                }
                
                $allVitals[] = [
                    'date' => $consultation->getDateConsultation() ? $consultation->getDateConsultation()->format('d/m/Y') : 'N/A',
                    'time' => $consultation->getTimeConsultation() ? $consultation->getTimeConsultation()->format('H:i') : '',
                    'bloodPressure' => $bloodPressure ?? '--',
                    'heartRate' => $vitals['heartRate'] ?? $vitals['pulse'] ?? null,
                    'temperature' => $vitals['temperature'] ?? null,
                    'weight' => $vitals['weight'] ?? null,
                    'height' => $vitals['height'] ?? null,
                    'spo2' => $vitals['oxygenSaturation'] ?? $vitals['spo2'] ?? null,
                    'consultationId' => $consultation->getId(),
                ];
            }
            
            // Timeline for single consultation
            $timelineData[] = [
                'id' => $consultation->getId(),
                'type' => 'consultation',
                'typeLabel' => 'Consultation',
                'title' => $consultation->getReasonForVisit() ?? 'Consultation',
                'description' => $consultation->getSymptomsDescription() ?? $consultation->getNotes() ?? 'Pas de description',
                'date' => $consultation->getDateConsultation() ? $consultation->getDateConsultation()->format('d/m/Y') : 'N/A',
                'status' => $consultation->getStatus(),
            ];
            
            // Medications from single consultation
            $ordonnances = $em->getRepository(Ordonnance::class)->findBy(['consultation' => $consultation]);
            foreach ($ordonnances as $ord) {
                $allMedications[] = [
                    'id' => $ord->getId(),
                    'name' => $ord->getMedicament(),
                    'dosage' => $ord->getDosage(),
                    'frequency' => $ord->getFrequency(),
                    'instructions' => $ord->getInstructions(),
                    'date' => $ord->getDateOrdonnance() ? $ord->getDateOrdonnance()->format('d/m/Y') : '--',
                    'consultationId' => $consultation->getId(),
                ];
            }
            
            // Examens from single consultation
            $examens = $em->getRepository(Examens::class)->findBy(['consultation' => $consultation]);
            foreach ($examens as $exam) {
                $allExamens[] = [
                    'id' => $exam->getId(),
                    'name' => $exam->getNomExamen(),
                    'type' => $exam->getTypeExamen(),
                    'result' => $exam->getResultat(),
                    'date' => $exam->getDateExamen() ? $exam->getDateExamen()->format('d/m/Y') : '--',
                    'consultationId' => $consultation->getId(),
                    'status' => $exam->getStatus(),
                    'resultFile' => $exam->getResultFile(),
                    'doctorAnalysis' => $exam->getDoctorAnalysis(),
                    'doctorTreatment' => $exam->getDoctorTreatment(),
                ];
            }
            
            $allConsultationsData[] = [
                'id' => $consultation->getId(),
                'date' => $consultation->getDateConsultation() ? $consultation->getDateConsultation()->format('d/m/Y') : 'N/A',
                'time' => $consultation->getTimeConsultation() ? $consultation->getTimeConsultation()->format('H:i') : '',
                'reasonForVisit' => $consultation->getReasonForVisit(),
                'status' => $consultation->getStatus(),
                'isCurrent' => true,
            ];
        }
        
        return $this->render('doctor/patient-chart.html.twig', [
            'page_title' => 'Dossier Médical',
            'consultation_id' => $id,
            'consultation' => $currentConsultation,
            'patient_data' => $patientData,
            'vital_signs' => $allVitals,
            'medications_data' => $allMedications,
            'examens_data' => $allExamens,
            'timeline_data' => $timelineData,
            'all_consultations' => $allConsultationsData,
        ]);
    }

    /**
     * API - Patient chart data (based on consultation)
     */
    #[Route('/doctor/api/patient-chart/{id}', name: 'health_doctor_patient_chart_api', methods: ['GET'])]
    public function getPatientChartData(int $id, EntityManagerInterface $em): JsonResponse
    {
        $consultation = $em->getRepository(Consultation::class)->find($id);
        
        if (!$consultation) {
            return $this->json([
                'success' => false,
                'message' => 'Consultation non trouvee',
            ], 404);
        }
        
        $ordonnances = $em->getRepository(Ordonnance::class)->findBy(['consultation' => $consultation]);
        $examens = $em->getRepository(Examens::class)->findBy(['consultation' => $consultation]);
        
        $chartData = $this->buildPatientChartData($consultation, $ordonnances, $examens);
        
        return $this->json([
            'success' => true,
            'data' => $chartData,
        ]);
    }

    /**
     * Helper - Build patient chart data from consultation
     */
    private function buildPatientChartData(Consultation $consultation, array $ordonnances, array $examens): array
    {
        $patientName = $consultation->getReasonForVisit() ?: 'Patient';
        $diagnoses = $consultation->getDiagnoses();
        $conditions = is_array($diagnoses) ? array_values($diagnoses) : [];
        
        $vitals = $consultation->getVitals();
        $vitals = is_array($vitals) ? $vitals : [];
        
        $vitalSigns = [];
        if (!empty($vitals)) {
            // Handle both flat and nested blood pressure structures
            $bpS = $vitals['bloodPressureSystolic'] ?? null;
            $bpD = $vitals['bloodPressureDiastolic'] ?? null;
            
            // Check for nested bloodPressure object
            if (isset($vitals['bloodPressure']) && is_array($vitals['bloodPressure'])) {
                $bpS = $bpS ?? $vitals['bloodPressure']['systolic'] ?? null;
                $bpD = $bpD ?? $vitals['bloodPressure']['diastolic'] ?? null;
            }
            
            $bloodPressure = null;
            if ($bpS && $bpD) {
                $bloodPressure = $bpS . '/' . $bpD;
            } elseif (isset($vitals['bloodPressure']) && is_string($vitals['bloodPressure'])) {
                $bloodPressure = $vitals['bloodPressure'];
            }
            
            $vitalSigns[] = [
                'date' => $consultation->getDateConsultation() ? $consultation->getDateConsultation()->format('d/m/Y') : 'N/A',
                'time' => $consultation->getTimeConsultation() ? $consultation->getTimeConsultation()->format('H:i') : '',
                'bloodPressure' => $bloodPressure ?? '--',
                'heartRate' => $vitals['heartRate'] ?? $vitals['pulse'] ?? null,
                'temperature' => $vitals['temperature'] ?? null,
                'weight' => $vitals['weight'] ?? null,
                'height' => $vitals['height'] ?? null,
                'spo2' => $vitals['oxygenSaturation'] ?? $vitals['spo2'] ?? null,
            ];
        }
        
        $height = isset($vitals['height']) ? (float) $vitals['height'] : 0;
        $weight = isset($vitals['weight']) ? (float) $vitals['weight'] : 0;
        $bmi = 0;
        if ($height > 0 && $weight > 0) {
            $heightM = $height / 100;
            $bmi = round($weight / ($heightM * $heightM), 1);
        }
        
        $patientStatus = 'stable';
        if ($consultation->getStatus() === 'completed') {
            $patientStatus = 'active';
        } elseif ($consultation->getStatus() === 'emergency') {
            $patientStatus = 'critical';
        }
        
        $patient = [
            'id' => $consultation->getId(),
            'name' => $patientName,
            'age' => '--',
            'gender' => 'M',
            'birthDate' => '--',
            'fileNumber' => 'CONS-' . str_pad((string) $consultation->getId(), 4, '0', STR_PAD_LEFT),
            'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($patientName) . '&background=00A790&color=fff',
            'status' => $patientStatus,
            'healthScore' => $this->calculateHealthScoreFromVitals($vitals),
            'conditions' => array_slice($conditions, 0, 5),
            'lastVisitDate' => $consultation->getDateConsultation() ? $consultation->getDateConsultation()->format('d/m/Y') : '--',
            'nextAppointment' => null,
            'bloodType' => '--',
            'height' => $height,
            'weight' => $weight,
            'bmi' => $bmi,
            'phone' => '--',
            'email' => '--',
            'address' => '--',
            'emergencyContact' => [
                'name' => '--',
                'relation' => '--',
                'phone' => '--',
            ],
            'allergies' => [],
            'medications' => [],
        ];
        
        $timeline = [
            [
                'id' => $consultation->getId(),
                'type' => 'symptom',
                'typeLabel' => 'Consultation',
                'title' => $consultation->getReasonForVisit() ?? 'Consultation',
                'description' => $consultation->getSymptomsDescription() ?? $consultation->getNotes() ?? 'Pas de description',
                'date' => $consultation->getDateConsultation() ? $consultation->getDateConsultation()->format('d/m/Y') : 'N/A',
                'severity' => $consultation->getStatus() === 'emergency' ? 5 : 2,
            ],
        ];
        
        $symptoms = [];
        if ($consultation->getSymptomsDescription()) {
            $symptoms[] = [
                'id' => 1,
                'name' => $consultation->getReasonForVisit() ?? 'Symptome',
                'date' => $consultation->getDateConsultation() ? $consultation->getDateConsultation()->format('d/m/Y') : 'N/A',
                'intensity' => 5,
                'status' => $consultation->getStatus() === 'completed' ? 'resolved' : 'active',
                'description' => $consultation->getSymptomsDescription(),
            ];
        }
        
        $medications = [];
        foreach ($ordonnances as $ord) {
            $medications[] = [
                'id' => $ord->getId(),
                'name' => $ord->getMedicament() ?? 'Medicament',
                'dosage' => $ord->getDosage() ?? '--',
                'frequency' => $ord->getFrequency() ?? '--',
                'active' => true,
            ];
            
            $timeline[] = [
                'id' => 'MED-' . $ord->getId(),
                'type' => 'medication',
                'typeLabel' => 'Traitement',
                'title' => 'Prescription: ' . ($ord->getMedicament() ?? 'Medicament'),
                'description' => ($ord->getDosage() ?? '') . ' - ' . ($ord->getInstructions() ?? ''),
                'date' => $ord->getDateOrdonnance() ? $ord->getDateOrdonnance()->format('d/m/Y') : 'N/A',
            ];
        }
        
        foreach ($examens as $exam) {
            $timeline[] = [
                'id' => 'EXAM-' . $exam->getId(),
                'type' => 'lab',
                'typeLabel' => 'Examen',
                'title' => $exam->getNomExamen() ?? $exam->getTypeExamen() ?? 'Examen',
                'description' => $exam->getResultat() ?? 'En attente',
                'date' => $exam->getDateExamen() ? $exam->getDateExamen()->format('d/m/Y') : 'N/A',
            ];
        }
        
        usort($timeline, function ($a, $b) {
            $dateA = ($a['date'] ?? 'N/A') === 'N/A' ? '1970-01-01' : $a['date'];
            $dateB = ($b['date'] ?? 'N/A') === 'N/A' ? '1970-01-01' : $b['date'];
            return $dateB <=> $dateA;
        });
        
        $followUp = $consultation->getFollowUp();
        $followUp = is_array($followUp) ? $followUp : [];
        $treatmentGoals = $followUp['goals'] ?? [];
        $treatmentFollowUps = $followUp['followUps'] ?? [];
        
        if (empty($treatmentGoals) && !empty($medications)) {
            $treatmentGoals = array_map(function ($med, $index) {
                $label = trim(($med['name'] ?? 'Médicament') . ' ' . ($med['dosage'] ?? ''));
                return [
                    'id' => $med['id'] ?? ($index + 1),
                    'description' => 'Traitement: ' . $label,
                    'completed' => false,
                    'deadline' => 'En cours',
                ];
            }, $medications, array_keys($medications));
        }
        
        $treatment = [
            'adherence' => (int) ($followUp['adherence'] ?? (!empty($medications) ? 80 : 0)),
            'goals' => $treatmentGoals,
            'followUps' => $treatmentFollowUps,
        ];
        
        $patient['medications'] = $medications;
        
        return [
            'patient' => $patient,
            'timeline' => $timeline,
            'symptoms' => $symptoms,
            'medications' => $medications,
            'treatment' => $treatment,
            'vitalSigns' => $vitalSigns,
        ];
    }

    /**
     * Helper - Health score from vitals
     */
    private function calculateHealthScoreFromVitals(array $vitals): int
    {
        $score = 85;
        
        if (isset($vitals['bloodPressureSystolic']) && isset($vitals['bloodPressureDiastolic'])) {
            $systolic = (int) $vitals['bloodPressureSystolic'];
            $diastolic = (int) $vitals['bloodPressureDiastolic'];
            
            if ($systolic > 140 || $diastolic > 90) {
                $score -= 15;
            } elseif ($systolic < 90 || $diastolic < 60) {
                $score -= 10;
            }
        }
        
        if (isset($vitals['temperature'])) {
            $temp = (float) $vitals['temperature'];
            if ($temp > 38 || $temp < 36) {
                $score -= 10;
            }
        }
        
        if (isset($vitals['heartRate'])) {
            $heartRate = (int) $vitals['heartRate'];
            if ($heartRate > 100 || $heartRate < 60) {
                $score -= 5;
            }
        }
        
        if (isset($vitals['oxygenSaturation'])) {
            $o2 = (int) $vitals['oxygenSaturation'];
            if ($o2 < 95) {
                $score -= 15;
            }
        }
        
        return max(0, min(100, $score));
    }

    /**
     * Doctor Interface - Patient Chart by Patient UUID
     * Affiche le dossier médical complet d'un patient avec toutes ses consultations
     */
    #[Route('/doctor/patient-chart/{uuid}', name: 'doctor_patient_chart_by_uuid', methods: ['GET'])]
    public function doctorPatientChartByUuid(string $uuid, EntityManagerInterface $em): Response
    {
        // Use the base UserRepository - Doctrine STI will return the correct subclass
        $user = $em->getRepository(User::class)->findOneBy(['uuid' => $uuid]);
        
        // Debug: Log what we found
        $debugInfo = [
            'uuid' => $uuid,
            'user_found' => $user ? 'yes' : 'no',
            'user_class' => $user ? get_class($user) : 'null',
            'is_patient' => $user instanceof Patient ? 'yes' : 'no',
        ];
        
        // Check if user exists and is a Patient instance
        if (!$user) {
            // Add debug info to the response for troubleshooting
            $patientData = [
                'id' => $uuid,
                'name' => 'Patient non trouvé (User not found)',
                'firstName' => '',
                'lastName' => '',
                'email' => '--',
                'phone' => '--',
                'age' => '--',
                'gender' => 'M',
                'avatar' => 'https://ui-avatars.com/api/?name=Patient&background=00A790&color=fff',
                'birthDate' => '--',
                'fileNumber' => 'PAT-' . substr($uuid, 0, 8),
                'status' => 'active',
                'healthScore' => 85,
                'conditions' => [],
                'lastVisitDate' => '--',
                'nextAppointment' => null,
                'bloodType' => '--',
                'height' => 0,
                'weight' => 0,
                'bmi' => 0,
                'address' => '--',
                'emergencyContact' => [
                    'name' => '--',
                    'relation' => '--',
                    'phone' => '--'
                ],
                'allergies' => [],
                'medications' => [],
                'debug' => $debugInfo,
            ];
            
            return $this->render('doctor/patient-chart.html.twig', [
                'page_title' => 'Dossier Médical',
                'patient_data' => $patientData,
                'vital_signs' => [],
                'medications_data' => [],
                'examens_data' => [],
                'timeline_data' => [],
                'consultation' => null,
                'consultation_id' => null,
                'consultation_data' => [],
                'all_consultations' => [],
            ]);
        }
        
        // If user exists but is not a Patient, still show their data
        // (they might be viewing their own chart or the role check might be too strict)
        $patient = $user; // Use the user object directly
        
        // Récupérer toutes les consultations du patient
        $consultations = $em->getRepository(Consultation::class)->findBy(
            ['patient' => $patient],
            ['date_consultation' => 'DESC']
        );
        
        // Debug: Log consultations
        error_log("Consultations found: " . count($consultations));
        
        // Préparer les données du patient avec tous les champs requis par le composant Alpine
        // Patient extends User, so all User fields are available
        $firstName = $patient->getFirstName() ?? '';
        $lastName = $patient->getLastName() ?? '';
        $fullName = trim($firstName . ' ' . $lastName);
        if (empty($fullName)) {
            $fullName = 'Patient ' . substr($patient->getUuid(), 0, 8);
        }
        
        // Debug: Log patient name
        error_log("Patient name: " . $fullName);
        
        // Agréger toutes les données de toutes les consultations
        $allVitals = [];
        $allMedications = [];
        $allExamens = [];
        $timelineData = [];
        $patientHeight = 0;
        $patientWeight = 0;
        
        foreach ($consultations as $consultation) {
            // Vitals
            $vitals = $consultation->getVitals();
            if (!empty($vitals)) {
                // Handle both flat and nested blood pressure structures
                $bpS = $vitals['bloodPressureSystolic'] ?? null;
                $bpD = $vitals['bloodPressureDiastolic'] ?? null;
                
                // Check for nested bloodPressure object
                if (isset($vitals['bloodPressure']) && is_array($vitals['bloodPressure'])) {
                    $bpS = $bpS ?? $vitals['bloodPressure']['systolic'] ?? null;
                    $bpD = $bpD ?? $vitals['bloodPressure']['diastolic'] ?? null;
                }
                
                $bloodPressure = null;
                if ($bpS && $bpD) {
                    $bloodPressure = $bpS . '/' . $bpD;
                } elseif (isset($vitals['bloodPressure']) && is_string($vitals['bloodPressure'])) {
                    $bloodPressure = $vitals['bloodPressure'];
                }
                
                $allVitals[] = [
                    'date' => $consultation->getDateConsultation() ? $consultation->getDateConsultation()->format('d/m/Y') : 'N/A',
                    'time' => $consultation->getTimeConsultation() ? $consultation->getTimeConsultation()->format('H:i') : '',
                    'bloodPressure' => $bloodPressure ?? '--',
                    'heartRate' => $vitals['heartRate'] ?? $vitals['pulse'] ?? null,
                    'temperature' => $vitals['temperature'] ?? null,
                    'weight' => $vitals['weight'] ?? null,
                    'height' => $vitals['height'] ?? null,
                    'spo2' => $vitals['oxygenSaturation'] ?? $vitals['spo2'] ?? null,
                ];
                
                // Get height and weight from vitals (use latest values)
                if (isset($vitals['height']) && $vitals['height'] > 0) {
                    $patientHeight = (float) $vitals['height'];
                }
                if (isset($vitals['weight']) && $vitals['weight'] > 0) {
                    $patientWeight = (float) $vitals['weight'];
                }
            }
            
            // Medications (ordonnances)
            $ordonnances = $em->getRepository(Ordonnance::class)->findBy(['consultation' => $consultation]);
            foreach ($ordonnances as $ord) {
                $allMedications[] = [
                    'id' => $ord->getId(),
                    'name' => $ord->getMedicament(),
                    'dosage' => $ord->getDosage(),
                    'frequency' => $ord->getFrequency(),
                    'instructions' => $ord->getInstructions(),
                    'date' => $ord->getDateOrdonnance() ? $ord->getDateOrdonnance()->format('d/m/Y') : '--',
                ];
            }
            
            // Examens
            $examens = $em->getRepository(Examens::class)->findBy(['consultation' => $consultation]);
            foreach ($examens as $exam) {
                $allExamens[] = [
                    'id' => $exam->getId(),
                    'name' => $exam->getNomExamen(),
                    'type' => $exam->getTypeExamen(),
                    'result' => $exam->getResultat(),
                    'date' => $exam->getDateExamen() ? $exam->getDateExamen()->format('d/m/Y') : '--',
                    'status' => $exam->getStatus(),
                    'resultFile' => $exam->getResultFile(),
                    'doctorAnalysis' => $exam->getDoctorAnalysis(),
                    'doctorTreatment' => $exam->getDoctorTreatment(),
                ];
            }
            
            // Timeline
            $timelineData[] = [
                'id' => $consultation->getId(),
                'type' => 'consultation',
                'typeLabel' => 'Consultation',
                'title' => $consultation->getReasonForVisit() ?? 'Consultation',
                'description' => $consultation->getSymptomsDescription() ?? $consultation->getNotes() ?? 'Pas de description',
                'date' => $consultation->getDateConsultation() ? $consultation->getDateConsultation()->format('d/m/Y') : 'N/A',
                'status' => $consultation->getStatus(),
            ];
        }
        
        // Populate medications from ordonnances
        $patientMedications = [];
        foreach ($allMedications as $med) {
            $patientMedications[] = [
                'id' => $med['id'],
                'name' => $med['name'],
                'dosage' => $med['dosage'],
                'frequency' => $med['frequency'],
                'active' => true, // Default to active
            ];
        }

        // Get last visit date from latest consultation
        $lastVisitDate = '--';
        if (!empty($consultations)) {
            $latestConsultation = $consultations[0];
            if ($latestConsultation->getDateConsultation()) {
                $lastVisitDate = $latestConsultation->getDateConsultation()->format('d/m/Y');
            }
        }

        // Get next appointment (scheduled or pending)
        $nextAppointment = null;
        foreach ($consultations as $consultation) {
            if (in_array($consultation->getStatus(), ['scheduled', 'pending']) && $consultation->getDateConsultation() > new \DateTime()) {
                $nextAppointment = $consultation->getDateConsultation()->format('d/m/Y');
                break;
            }
        }

        $patientData = [
            'id' => $patient->getUuid(),
            'name' => $fullName,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'email' => $patient->getEmail() ?? '--',
            'phone' => $patient->getPhone() ?? '--',
            'age' => $patient->getBirthdate() ? $patient->getBirthdate()->diff(new \DateTime())->y : '--',
            'gender' => 'M', // Default - could be stored in user entity if needed
            'avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=00A790&color=fff',
            'birthDate' => $patient->getBirthdate() ? $patient->getBirthdate()->format('d/m/Y') : '--',
            // Additional fields required by Alpine component
            'fileNumber' => 'PAT-' . substr($patient->getUuid(), 0, 8),
            'status' => 'active',
            'healthScore' => 85,
            'conditions' => [],
            'lastVisitDate' => $lastVisitDate,
            'nextAppointment' => $nextAppointment,
            'bloodType' => '--',
            'height' => $patientHeight,
            'weight' => $patientWeight,
            'bmi' => 0,
            'address' => $patient->getAddress() ?? '--',
            'emergencyContact' => [
                'name' => '--',
                'relation' => '--',
                'phone' => '--'
            ],
            'allergies' => [], // TODO: Add allergies from user entity when available
            'medications' => $patientMedications,
        ];
        
        // Convert consultations to array format for JSON encoding
        $allConsultationsData = [];
        foreach ($consultations as $cons) {
            $allConsultationsData[] = [
                'id' => $cons->getId(),
                'date' => $cons->getDateConsultation() ? $cons->getDateConsultation()->format('d/m/Y') : 'N/A',
                'time' => $cons->getTimeConsultation() ? $cons->getTimeConsultation()->format('H:i') : '',
                'reasonForVisit' => $cons->getReasonForVisit(),
                'status' => $cons->getStatus(),
                'consultationType' => $cons->getConsultationType(),
                'appointmentMode' => $cons->getAppointmentMode(),
            ];
        }
        
        // Build symptoms data from consultations
        $symptomsData = [];
        foreach ($consultations as $cons) {
            $symptomsDesc = $cons->getSymptomsDescription();
            if (!empty($symptomsDesc)) {
                // Parse symptoms from description (assuming comma-separated or structured format)
                $symptomNames = array_map('trim', explode(',', $symptomsDesc));
                foreach ($symptomNames as $idx => $symptomName) {
                    if (!empty($symptomName)) {
                        $symptomsData[] = [
                            'id' => $cons->getId() . '_' . $idx,
                            'date' => $cons->getDateConsultation() ? $cons->getDateConsultation()->format('d/m/Y') : 'N/A',
                            'name' => $symptomName,
                            'intensity' => 5, // Default intensity
                            'duration' => '--',
                            'status' => 'resolved', // Past consultations have resolved symptoms
                        ];
                    }
                }
            }
        }
        
        // Build treatment data
        $treatmentData = [
            'adherence' => 85, // Default adherence percentage
            'goals' => [], // Treatment goals could be stored in a separate entity
            'followUps' => [], // Follow-up appointments
        ];
        
        // Add follow-up appointments from consultations
        foreach ($consultations as $cons) {
            if ($cons->getStatus() === 'scheduled' || $cons->getStatus() === 'pending') {
                $treatmentData['followUps'][] = [
                    'id' => $cons->getId(),
                    'type' => $cons->getConsultationType() ?? 'Consultation',
                    'date' => $cons->getDateConsultation() ? $cons->getDateConsultation()->format('d/m/Y') : 'N/A',
                    'time' => $cons->getTimeConsultation() ? $cons->getTimeConsultation()->format('H:i') : '',
                    'status' => $cons->getStatus(),
                ];
            }
        }
        
        // Get the most recent consultation to populate consultation_data
        $consultationData = [];
        if (!empty($consultations)) {
            $latestConsultation = $consultations[0];
            $consultationData = [
                'id' => $latestConsultation->getId(),
                'date' => $latestConsultation->getDateConsultation() ? $latestConsultation->getDateConsultation()->format('d/m/Y') : 'N/A',
                'time' => $latestConsultation->getTimeConsultation() ? $latestConsultation->getTimeConsultation()->format('H:i') : '',
                'reasonForVisit' => $latestConsultation->getReasonForVisit(),
                'symptomsDescription' => $latestConsultation->getSymptomsDescription(),
                'diagnoses' => $latestConsultation->getDiagnoses(),
                'assessment' => $latestConsultation->getAssessment(),
                'plan' => $latestConsultation->getPlan(),
                'notes' => $latestConsultation->getNotes(),
                'status' => $latestConsultation->getStatus(),
                'soapNotes' => [
                    'subjective' => $latestConsultation->getSubjective(),
                    'objective' => $latestConsultation->getObjective(),
                    'assessment' => $latestConsultation->getAssessment(),
                    'plan' => $latestConsultation->getPlan(),
                ],
                'appointmentMode' => $latestConsultation->getAppointmentMode(),
                'consultationType' => $latestConsultation->getConsultationType(),
                'duration' => $latestConsultation->getDuration(),
                'location' => $latestConsultation->getLocation(),
            ];
        }

        return $this->render('doctor/patient-chart.html.twig', [
            'page_title' => 'Dossier Médical - ' . $patientData['name'],
            'patient_data' => $patientData,
            'vital_signs' => $allVitals,
            'medications_data' => $allMedications,
            'examens_data' => $allExamens,
            'timeline_data' => $timelineData,
            'symptoms_data' => $symptomsData,
            'treatment_data' => $treatmentData,
            'consultation' => null, // Not a single consultation view
            'consultation_id' => null,
            'consultation_data' => $consultationData, // Populate with latest consultation data
            'all_consultations' => $allConsultationsData, // All consultations for this patient as array
        ]);
    }

    /**
     * Doctor Interface - Clinical Notes
     * Interface pour les notes cliniques SOAP
     */
    #[Route('/doctor/patient/{id}/notes', name: 'doctor_patient_notes', methods: ['GET'])]
    public function doctorClinicalNotes(string $id): Response
    {
        return $this->render('doctor/clinical-notes.html.twig', [
            'page_title' => 'Notes Cliniques',
            'patient_id' => $id,
        ]);
    }
    
    private function processValidEntry(
        $form,
        EntityManagerInterface $entityManager,
        HealthjournalRepository $journalRepo,
        HealthentryRepository $entryRepo,
        Request $request,
        Security $security
    ): Response {
        $healthentry = $form->getData();
        
        // Remove empty symptoms (those without type) before saving
        if ($healthentry->getSymptoms()->count() > 0) {
            $symptomsToRemove = [];
            foreach ($healthentry->getSymptoms() as $symptom) {
                if (null === $symptom->getType() || '' === $symptom->getType()) {
                    $symptomsToRemove[] = $symptom;
                }
            }
            foreach ($symptomsToRemove as $symptom) {
                $healthentry->removeSymptom($symptom);
            }
        }
        
        // Find the journal based on entry date - entries are automatically assigned
        // to the journal whose date range includes the entry date
        $entryDate = $healthentry->getDate();
        $journal = null;
        $user = $security->getUser();
        
        if (null !== $entryDate && $user) {
            // Find journal whose date range includes this entry date AND belongs to current user
            $journal = $journalRepo->createQueryBuilder('j')
                ->andWhere('j.datedebut <= :entryDate')
                ->andWhere('j.datefin >= :entryDate')
                ->andWhere('j.user = :user')
                ->setParameter('entryDate', $entryDate)
                ->setParameter('user', $user)
                ->getQuery()
                ->getOneOrNullResult();
        }
        
        // Fallback: if no journal found by date, use first journal for this user
        if (null === $journal && $user) {
            $journal = $journalRepo->findOneBy(['user' => $user]);
        }
        
        // Last resort: create a new journal for this user
        if (null === $journal && $user) {
            $journal = new Healthjournal();
            $journal->setName('Journal Principal');
            $journal->setDatedebut(new \DateTime());
            $journal->setUser($user);
            $entityManager->persist($journal);
            $entityManager->flush();
        }
        
        // If still no journal (user not logged in), show error
        if (null === $journal) {
            $form->addError(new \Symfony\Component\Form\FormError(
                'Vous devez être connecté pour créer une entrée.'
            ));
            return $this->render('health/accessible/journal-entry.html.twig', [
                'controller_name' => 'HealthController',
                'form' => $form->createView(),
            ]);
        }
        
        $healthentry->setJournal($journal);
        
        // Check for duplicate entry
        $date = $healthentry->getDate();
        if (null !== $date) {
            $existingEntry = $entryRepo->findOneBy(['date' => $date, 'journal' => $journal]);
            if (null !== $existingEntry) {
                $form->addError(new \Symfony\Component\Form\FormError(
                    'Une entrée existe déjà pour cette date. Veuillez modifier l\'entrée existante.'
                ));
                return $this->render('health/accessible/journal-entry.html.twig', [
                    'controller_name' => 'HealthController',
                    'form' => $form->createView(),
                ]);
            }
        }
        
        $entityManager->persist($healthentry);
        $entityManager->flush();
        
        $this->addFlash('success', 'Entrée de journal créée avec succès');
        
        return $this->redirectToRoute('app_health_journal_accessible');
    }
}
