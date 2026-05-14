<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Entity\Ordonnance;
use App\Entity\Examens;
use App\Entity\Patient;
use App\Entity\Consultation;
use App\Entity\User;
use App\Entity\DoctorAvailability;
use App\Entity\DoctorLocation;
use App\Entity\DoctorLeave;
use App\Entity\DoctorSubstitution;
use App\Entity\Medecin;
use App\Form\SoapType;
use App\Form\OrdonnanceType;
use App\Form\ExamenType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/health/doctor')]
class DoctorController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /*******************************************************************
     * DASHBOARD ET PAGES PRINCIPALES
     *******************************************************************/

    /**
     * Dashboard du médecin
     */
    #[Route('/dashboard', name: 'doctor_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $patientCount = $this->em->getRepository(Patient::class)->count([]);
        $consultationCount = $this->em->getRepository(Consultation::class)->count([]);
        $todayConsultations = $this->em->getRepository(Consultation::class)->createQueryBuilder('c')
            ->where('c.date_consultation >= :today_start')
            ->andWhere('c.date_consultation < :today_end')
            ->setParameter('today_start', (new \DateTime())->setTime(0, 0, 0))
            ->setParameter('today_end', (new \DateTime())->setTime(23, 59, 59))
            ->getQuery()
            ->getResult();
        
        $nextAppointments = $this->em->getRepository(Consultation::class)->createQueryBuilder('c')
            ->where('c.date_consultation >= :now')
            ->andWhere('c.status = :scheduled')
            ->setParameter('now', new \DateTime())
            ->setParameter('scheduled', 'scheduled')
            ->orderBy('c.date_consultation', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('doctor/dashboard.html.twig', [
            'patientCount' => $patientCount,
            'consultationCount' => $consultationCount,
            'todayConsultations' => count($todayConsultations),
            'nextAppointments' => $nextAppointments,
        ]);
    }

    /**

     * Doctor Dashboard - Main entry point for physicians
     */
    /*#[Route('/dashboard', name: 'doctor_dashboard')]
    public function dashboard(): Response
    {
        $stats = [
            'totalPatients' => 156,
            'appointmentsToday' => 8,
            'pendingPrescriptions' => 5,
            'unreadMessages' => 12,
        ];

        $todayAppointments = [
            [
                'id' => 1,
                'time' => '09:00',
                'patientName' => 'Ahmed Ben Ali',
                'type' => 'Consultation',
                'status' => 'waiting',
                'avatar' => 'A',
            ],
            [
                'id' => 2,
                'time' => '09:30',
                'patientName' => 'Fatma Trabelsi',
                'type' => 'Follow-up',
                'status' => 'in_progress',
                'avatar' => 'F',
            ],
            [
                'id' => 3,
                'time' => '10:00',
                'patientName' => 'Mohamed Kouki',
                'type' => 'New Patient',
                'status' => 'confirmed',
                'avatar' => 'M',
            ],
        ];

        $recentPatients = [
            [
                'id' => 1,
                'name' => 'Ahmed Ben Ali',
                'lastVisit' => '5 days ago',
                'condition' => 'Diabetes Type 2',
                'status' => 'active',
            ],
            [
                'id' => 2,
                'name' => 'Fatma Trabelsi',
                'lastVisit' => '2 weeks ago',
                'condition' => 'Asthma',
                'status' => 'active',
            ],
        ];

        return $this->render('doctor/dashboard.html.twig', [
            'pageTitle' => 'Doctor Dashboard - WellCare Connect',
            'stats' => $stats,
            'todayAppointments' => $todayAppointments,
            'recentPatients' => $recentPatients,
        ]);
    }

    /**
     * Patient List - Affiche la liste des patients du médecin
     * Seulement les patients avec rendez-vous acceptés
     */
    #[Route('/patients', name: 'doctor_patients', methods: ['GET'])]
    public function patientList(): Response
    {
        // Récupérer le médecin actuellement connecté
        $user = $this->getUser();
        
        // Récupérer uniquement les consultations acceptées pour ce médecin
        $consultations = $this->em->getRepository(Consultation::class)
            ->findAcceptedByMedecin($user->getUuid());

        $consultationsArray = [];
        foreach ($consultations as $consultation) {
            $consultationsArray[] = $this->formatConsultationForList($consultation);
        }

        return $this->render('doctor/patient-list.html.twig', [
            'consultationsData' => $consultationsArray,
        ]);
    }

    /*******************************************************************
     * GESTION DES CONSULTATIONS (API)
     *******************************************************************/

    /**
     * Get Consultations - Récupère la liste des consultations acceptées (AJAX)
     * Filtre par médecin connecté
     */
    #[Route('/api/consultations', name: 'doctor_get_consultations', methods: ['GET'])]
    public function getConsultations(Request $request): JsonResponse
    {
        try {
            // Récupérer le médecin actuellement connecté
            $user = $this->getUser();
            
            $search = $request->query->get('search', '');
            $status = $request->query->get('status', '');
            $condition = $request->query->get('condition', '');
            $dateFrom = $request->query->get('dateFrom', '');
            $dateTo = $request->query->get('dateTo', '');
            $sortBy = $request->query->get('sortBy', 'lastVisit');
            $sortDir = $request->query->get('sortDir', 'DESC');
            $page = (int) $request->query->get('page', 1);
            $limit = (int) $request->query->get('limit', 100);

            $consultationRepository = $this->em->getRepository(Consultation::class);
            
            // Récupérer uniquement les consultations acceptées pour ce médecin
            $consultations = $consultationRepository->findAcceptedByMedecin($user->getUuid());

            $consultationsArray = [];
            foreach ($consultations as $consultation) {
                try {
                    $consultationsArray[] = $this->formatConsultationForList($consultation);
                } catch (\Exception $e) {
                    error_log('Erreur traitement consultation ' . $consultation->getId() . ': ' . $e->getMessage());
                    continue;
                }
            }

            return $this->json($consultationsArray);

        } catch (\Exception $e) {
            error_log('Erreur getConsultations: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());

            return $this->json([
                'error' => true,
                'message' => 'Erreur lors de la récupération des consultations',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper - Calcule le score de sante
     */
    private function calculateHealthScore(Consultation $consultation): int
    {
        $score = 85;
        
        $vitals = $consultation->getVitals();
        
        if (is_array($vitals)) {
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
        }
        
        return max(0, min(100, $score));
    }

    private function formatConsultationForList(Consultation $consultation): array
    {
        // Récupérer les données du patient depuis la relation
        $patient = $consultation->getPatient();
        
        // Utiliser les vraies données patient si disponibles
        if ($patient) {
            $patientName = trim(($patient->getFirstName() ?? '') . ' ' . ($patient->getLastName() ?? ''));
            if (empty($patientName) || $patientName === ' ') {
                $patientName = 'Patient #' . substr($patient->getUuid() ?? '', 0, 8);
            }
            
            $age = null;
            if ($patient->getBirthdate()) {
                $age = $patient->getBirthdate()->diff(new \DateTime())->y;
            }
            
            $gender = 'M'; // Default
            // Note: si vous avez un champ gender dans Patient, décommentez:
            // $gender = $patient->getGender() ?? 'M';
            
            $avatar = $patient->getAvatarUrl();
            if (empty($avatar)) {
                $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($patientName) . '&background=00A790&color=fff';
            }
            
            $email = $patient->getEmail() ?? '';
            $phone = $patient->getPhone() ?? '';
        } else {
            $patientName = 'Consultation ' . $consultation->getId();
            $age = 35;
            $gender = 'M';
            $avatar = '/images/avatars/default.png';
            $email = '';
            $phone = '';
        }

        $consultStatus = 'stable';
        $assessment = strtolower($consultation->getAssessment() ?? '');

        if (strpos($assessment, 'critique') !== false || strpos($assessment, 'urgent') !== false) {
            $consultStatus = 'critical';
        } elseif (strpos($assessment, 'suivi') !== false || strpos($assessment, 'contrôle') !== false) {
            $consultStatus = 'follow-up';
        } elseif ($consultation->getStatus() === 'completed') {
            $consultStatus = 'active';
        }

        $healthScore = $this->calculateHealthScore($consultation);

        $conditions = [];
        $diagnoses = $consultation->getDiagnoses();

        if (is_array($diagnoses) && !empty($diagnoses)) {
            $conditions = array_slice($diagnoses, 0, 3);
        }

        $consultationDate = '';
        $consultationTime = '';
        if ($consultation->getDateConsultation()) {
            $consultationDate = $consultation->getDateConsultation()->format('d/m/Y');
        }
        if ($consultation->getTimeConsultation()) {
            $consultationTime = $consultation->getTimeConsultation()->format('H:i');
        }

        return [
            'id' => $consultation->getId(),
            'consultationId' => $consultation->getId(),
            'patientId' => $patient ? $patient->getUuid() : $consultation->getId(),
            'name' => $patientName,
            'email' => $email,
            'phone' => $phone,
            'age' => $age,
            'gender' => $gender,
            'avatar' => $avatar,
            'fileNumber' => 'CONS-' . str_pad($consultation->getId(), 4, '0', STR_PAD_LEFT),
            'status' => $consultStatus,
            'healthScore' => $healthScore,
            'conditions' => $conditions,
            'lastVisitDate' => $consultationDate,
            'lastVisitTime' => $consultationTime,
            'reasonForVisit' => $consultation->getReasonForVisit(),
        ];
    }

    /**
     * Get Patients - Récupère la liste des patients (AJAX)
     */
    #[Route('/api/patients', name: 'doctor_get_patients', methods: ['GET'])]
    public function getPatients(Request $request): JsonResponse
    {
        try {
            $search = $request->query->get('search', '');
            $page = (int) $request->query->get('page', 1);
            $limit = (int) $request->query->get('limit', 100);

            $patientRepository = $this->em->getRepository(Patient::class);
            $qb = $patientRepository->createQueryBuilder('p');
            
            if ($search) {
                $qb->andWhere('p.nom LIKE :search OR p.prenom LIKE :search OR p.email LIKE :search')
                   ->setParameter('search', '%' . $search . '%');
            }
            
            $qb->setFirstResult(($page - 1) * $limit)
               ->setMaxResults($limit);
               
            $patients = $qb->getQuery()->getResult();
            
            $patientsArray = [];
            foreach ($patients as $patient) {
                try {
                    $age = null;
                    if ($patient->getDateNaissance()) {
                        $age = $patient->getDateNaissance()->diff(new \DateTime())->y;
                    }

                    $status = 'active';
                    if ($age && $age > 70) {
                        $status = 'follow-up';
                    }

                    $healthScore = rand(60, 95);

                    $conditions = [];
                    if ($patient->getAntecedentsMedicaux()) {
                        $conditions = array_slice(explode(',', $patient->getAntecedentsMedicaux()), 0, 3);
                    }

                    $lastVisitDate = '';
                    $lastVisitTime = '';
                    if ($patient->getDateDerniereVisite()) {
                        $lastVisitDate = $patient->getDateDerniereVisite()->format('d/m/Y');
                        $lastVisitTime = $patient->getDateDerniereVisite()->format('H:i');
                    }

                    $patientsArray[] = [
                        'id' => $patient->getId(),
                        'name' => trim($patient->getNom() . ' ' . $patient->getPrenom()),
                        'email' => $patient->getEmail(),
                        'phone' => $patient->getTelephone(),
                        'age' => $age,
                        'gender' => $patient->getSexe(),
                        'avatar' => '/images/avatars/default.png',
                        'fileNumber' => '2024-' . str_pad($patient->getId(), 3, '0', STR_PAD_LEFT),
                        'status' => $status,
                        'healthScore' => $healthScore,
                        'conditions' => $conditions,
                        'lastVisitDate' => $lastVisitDate,
                        'lastVisitTime' => $lastVisitTime,
                    ];
                } catch (\Exception $e) {
                    continue;
                }
            }

            return $this->json($patientsArray);
            
        } catch (\Exception $e) {
            error_log('Erreur getPatients: ' . $e->getMessage());
            
            return $this->json([
                'error' => true,
                'message' => 'Erreur lors de la récupération des patients',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Patient Details
     */
    #[Route('/api/patient/{id}', name: 'doctor_get_patient', methods: ['GET'])]
    public function getPatient(int $id): JsonResponse
    {
        $patient = $this->em->getRepository(Patient::class)->find($id);
        
        if (!$patient) {
            return $this->json([
                'success' => false,
                'message' => 'Patient non trouvé',
            ], 404);
        }

        $consultations = $this->em->getRepository(Consultation::class)->findBy([], ['date_consultation' => 'DESC'], 10);
        
        $medicalHistory = [];
        foreach ($consultations as $consultation) {
            $medicalHistory[] = [
                'date' => $consultation->getDateConsultation()->format('Y-m-d'),
                'type' => 'Consultation',
                'description' => $consultation->getReasonForVisit(),
                'doctor' => 'Dr. ' . ($this->getUser() ? $this->getUser()->getNom() : 'Médecin'),
            ];
        }

        $ordonnances = $this->em->getRepository(Ordonnance::class)->createQueryBuilder('o')
            ->join('o.consultation', 'c')
            ->orderBy('o.date_ordonnance', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
            
        $currentMedications = [];
        foreach ($ordonnances as $ordonnance) {
            $currentMedications[] = [
                'name' => $ordonnance->getMedicament(),
                'dosage' => $ordonnance->getDosage(),
                'startDate' => $ordonnance->getDateOrdonnance()->format('Y-m-d'),
            ];
        }

        $patientData = [
            'id' => $patient->getId(),
            'name' => $patient->getNom() . ' ' . $patient->getPrenom(),
            'email' => $patient->getEmail(),
            'phone' => $patient->getTelephone(),
            'age' => $patient->getDateNaissance() ? $patient->getDateNaissance()->diff(new \DateTime())->y : null,
            'gender' => $patient->getSexe(),
            'dateOfBirth' => $patient->getDateNaissance() ? $patient->getDateNaissance()->format('Y-m-d') : null,
            'address' => $patient->getAdresse(),
            'emergencyContact' => $patient->getContactUrgence(),
            'insurance' => $patient->getAssurance(),
            'insuranceNumber' => $patient->getNumeroAssurance(),
            'medicalHistory' => $medicalHistory,
            'currentMedications' => $currentMedications,
            'allergies' => $patient->getAllergies() ? explode(',', $patient->getAllergies()) : [],
            'conditions' => $patient->getAntecedentsMedicaux() ? explode(',', $patient->getAntecedentsMedicaux()) : [],
            'lastVisit' => $patient->getDateDerniereVisite(),
            'notes' => $patient->getNotes(),
        ];

        return $this->json([
            'success' => true,
            'data' => $patientData,
        ]);
    }

    /**
     * Add Patient
     */
    #[Route('/api/patient', name: 'doctor_add_patient', methods: ['POST'])]
    public function addPatient(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['name']) || !isset($data['email'])) {
            return $this->json([
                'success' => false,
                'message' => 'Données invalides',
            ], 400);
        }

        $patient = new Patient();
        
        $nameParts = explode(' ', $data['name'], 2);
        $patient->setNom($nameParts[0] ?? '');
        $patient->setPrenom($nameParts[1] ?? '');
        
        $patient->setEmail($data['email']);
        $patient->setTelephone($data['phone'] ?? '');
        $patient->setSexe($data['gender'] ?? '');
        
        if (isset($data['dateOfBirth'])) {
            $patient->setDateNaissance(new \DateTime($data['dateOfBirth']));
        }
        
        $patient->setAdresse($data['address'] ?? '');
        $patient->setContactUrgence($data['emergencyContact'] ?? '');
        $patient->setAssurance($data['insurance'] ?? '');
        $patient->setNumeroAssurance($data['insuranceNumber'] ?? '');
        $patient->setAllergies($data['allergies'] ?? '');
        $patient->setAntecedentsMedicaux($data['conditions'] ?? '');
        $patient->setNotes($data['notes'] ?? '');
        $patient->setDateInscription(new \DateTime());
        $patient->setDateDerniereVisite(new \DateTime());

        $this->em->persist($patient);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Patient ajouté avec succès',
            'data' => [
                'id' => $patient->getId(),
                'name' => $patient->getNom() . ' ' . $patient->getPrenom(),
                'email' => $patient->getEmail(),
            ],
        ]);
    }

    /**
     * Update Patient
     */
    #[Route('/api/patient/{id}', name: 'doctor_update_patient', methods: ['PUT'])]
    public function updatePatient(int $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'success' => false,
                'message' => 'Données invalides',
            ], 400);
        }

        $patient = $this->em->getRepository(Patient::class)->find($id);
        
        if (!$patient) {
            return $this->json([
                'success' => false,
                'message' => 'Patient non trouvé',
            ], 404);
        }

        if (isset($data['name'])) {
            $nameParts = explode(' ', $data['name'], 2);
            $patient->setNom($nameParts[0] ?? '');
            $patient->setPrenom($nameParts[1] ?? '');
        }
        
        if (isset($data['email'])) {
            $patient->setEmail($data['email']);
        }
        
        if (isset($data['phone'])) {
            $patient->setTelephone($data['phone']);
        }
        
        if (isset($data['gender'])) {
            $patient->setSexe($data['gender']);
        }
        
        if (isset($data['dateOfBirth'])) {
            $patient->setDateNaissance(new \DateTime($data['dateOfBirth']));
        }
        
        if (isset($data['address'])) {
            $patient->setAdresse($data['address']);
        }
        
        if (isset($data['emergencyContact'])) {
            $patient->setContactUrgence($data['emergencyContact']);
        }
        
        if (isset($data['insurance'])) {
            $patient->setAssurance($data['insurance']);
        }
        
        if (isset($data['insuranceNumber'])) {
            $patient->setNumeroAssurance($data['insuranceNumber']);
        }
        
        if (isset($data['allergies'])) {
            $patient->setAllergies(is_array($data['allergies']) ? implode(',', $data['allergies']) : $data['allergies']);
        }
        
        if (isset($data['conditions'])) {
            $patient->setAntecedentsMedicaux(is_array($data['conditions']) ? implode(',', $data['conditions']) : $data['conditions']);
        }
        
        if (isset($data['notes'])) {
            $patient->setNotes($data['notes']);
        }

        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Patient mis à jour avec succès',
            'data' => [
                'id' => $patient->getId(),
                'name' => $patient->getNom() . ' ' . $patient->getPrenom(),
                'email' => $patient->getEmail(),
            ],
        ]);
    }

    /**
     * Delete Patient
     */
    #[Route('/api/patient/{id}', name: 'doctor_delete_patient', methods: ['DELETE'])]
    public function deletePatient(int $id): JsonResponse
    {
        $patient = $this->em->getRepository(Patient::class)->find($id);
        
        if (!$patient) {
            return $this->json([
                'success' => false,
                'message' => 'Patient non trouvé',
            ], 404);
        }

        $consultations = $this->em->getRepository(Consultation::class)->findBy([], [], 1);
        
        if (count($consultations) > 0) {
            return $this->json([
                'success' => false,
                'message' => 'Impossible de supprimer le patient car il a des consultations associées',
            ], 400);
        }

        $this->em->remove($patient);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Patient supprimé avec succès',
        ]);
    }

    /*******************************************************************
     * GESTION DES NOTES CLINIQUES
     *******************************************************************/

    /**
     * Clinical Notes - Page des notes cliniques
     */
    #[Route('/clinical-notes', name: 'doctor_clinical_notes', methods: ['GET'])]
    public function clinicalNotes(Request $request): Response
    {
        $consultationId = $request->query->get('consultationId');
        $patientId = $request->query->get('patientId');
        
        $patient = null;
        $currentNoteData = null;
        $consultation = null;
        $patientData = null;

        if ($consultationId) {
            $consultation = $this->em->getRepository(Consultation::class)->find($consultationId);
            
            if (!$consultation) {
                $this->addFlash('error', 'Consultation non trouvée');
                return $this->redirectToRoute('doctor_clinical_notes');
            }
            
            // Récupérer le patient depuis la relation
            $patient = $consultation->getPatient();
            
            // Préparer les données du patient pour le template
            if ($patient) {
                $patientData = [
                    'id' => $patient->getUuid(),
                    'name' => trim(($patient->getFirstName() ?? '') . ' ' . ($patient->getLastName() ?? '')),
                    'firstName' => $patient->getFirstName(),
                    'lastName' => $patient->getLastName(),
                    'email' => $patient->getEmail(),
                    'phone' => $patient->getPhone(),
                    'age' => $patient->getBirthdate() ? $patient->getBirthdate()->diff(new \DateTime())->y : null,
                    'gender' => 'M', // Default, à adapter si vous avez un champ gender
                    'avatar' => $patient->getAvatarUrl() ?? 'https://ui-avatars.com/api/?name=' . urlencode(trim(($patient->getFirstName() ?? '') . ' ' . ($patient->getLastName() ?? ''))) . '&background=00A790&color=fff',
                ];
            }
            
            $currentNoteData = [
                'id' => $consultation->getId(),
                'chiefComplaint' => $consultation->getReasonForVisit(),
                'subjective' => $consultation->getSubjective(),
                'objective' => $consultation->getObjective(),
                'assessment' => $consultation->getAssessment(),
                'plan' => $consultation->getPlan(),
                'vitals' => $consultation->getVitals(),
                'date' => $consultation->getDateConsultation() ? $consultation->getDateConsultation()->format('Y-m-d') : '',
                'diagnoses' => $consultation->getDiagnoses() ?? [],
                'followUp' => $consultation->getFollowUp() ?? [],
                'medications' => $this->getMedicationsArray($consultation),
                'labTests' => $this->getExamsArray($consultation)
            ];
        } elseif ($patientId) {
            $patient = $this->em->getRepository(Patient::class)->find($patientId);
            
            if (!$patient) {
                $this->addFlash('error', 'Patient non trouvé');
                return $this->redirectToRoute('doctor_clinical_notes');
            }
            
            // Préparer les données du patient pour le template
            $patientData = [
                'id' => $patient->getUuid(),
                'name' => trim(($patient->getFirstName() ?? '') . ' ' . ($patient->getLastName() ?? '')),
                'firstName' => $patient->getFirstName(),
                'lastName' => $patient->getLastName(),
                'email' => $patient->getEmail(),
                'phone' => $patient->getPhone(),
                'age' => $patient->getBirthdate() ? $patient->getBirthdate()->diff(new \DateTime())->y : null,
                'gender' => 'M',
                'avatar' => $patient->getAvatarUrl() ?? 'https://ui-avatars.com/api/?name=' . urlencode(trim(($patient->getFirstName() ?? '') . ' ' . ($patient->getLastName() ?? ''))) . '&background=00A790&color=fff',
            ];
        }

        $history = [];
        if ($patient) {
            $history = $this->em->getRepository(Consultation::class)->findBy(
                ['patient' => $patient],
                ['date_consultation' => 'DESC']
            );
        } elseif ($consultation) {
            $history = [$consultation];
        }
        
        // If no specific patient or consultation, show recent consultations
        if (empty($history) && !$patient && !$consultation) {
            $recentConsultations = $this->em->getRepository(Consultation::class)->findBy(
                [],
                ['date_consultation' => 'DESC'],
                20
            );
            
            // Format recent consultations for display
            $historyData = array_map(function($c) {
                $vitals = $c->getVitals() ?: [];
                return [
                    'id' => $c->getId(),
                    'date' => $c->getDateConsultation() ? $c->getDateConsultation()->format('d/m/Y') : '',
                    'title' => 'Note SOAP',
                    'summary' => $c->getReasonForVisit() ?? 'Pas de motif',
                    'data' => [
                        'consultation' => [
                            'chiefComplaint' => $c->getReasonForVisit() ?? '',
                            'subjective' => $c->getSubjective() ?? '',
                            'objective' => $c->getObjective() ?? '',
                            'assessment' => $c->getAssessment() ?? '',
                            'plan' => $c->getPlan() ?? '',
                            'vitals' => [
                                'bloodPressure' => [
                                    'systolic' => $vitals['bloodPressure']['systolic'] ?? $vitals['bloodPressureSystolic'] ?? '',
                                    'diastolic' => $vitals['bloodPressure']['diastolic'] ?? $vitals['bloodPressureDiastolic'] ?? '',
                                ],
                                'pulse' => $vitals['pulse'] ?? $vitals['heartRate'] ?? '',
                                'temperature' => $vitals['temperature'] ?? '',
                                'spo2' => $vitals['spo2'] ?? $vitals['oxygenSaturation'] ?? '',
                            ],
                        ],
                        'diagnoses' => $c->getDiagnoses() ?? [],
                        'medications' => $this->getMedicationsArray($c),
                        'labTests' => $this->getExamsArray($c),
                        'followUp' => $c->getFollowUp() ?? [],
                    ],
                ];
            }, $recentConsultations);
        } else {
            $historyData = [];
            foreach ($history as $item) {
                $vitals = $item->getVitals();
                $vitals = is_array($vitals) ? $vitals : [];
                
                $historyData[] = [
                    'id' => $item->getId(),
                    'date' => $item->getDateConsultation() ? $item->getDateConsultation()->format('d/m/Y') : '',
                    'title' => 'Note SOAP',
                    'summary' => $item->getReasonForVisit() ?? '',
                    'data' => [
                        'consultation' => [
                            'chiefComplaint' => $item->getReasonForVisit() ?? '',
                            'subjective' => $item->getSubjective() ?? '',
                            'objective' => $item->getObjective() ?? '',
                            'assessment' => $item->getAssessment() ?? '',
                            'plan' => $item->getPlan() ?? '',
                            'vitals' => [
                                'bloodPressure' => [
                                    'systolic' => $vitals['bloodPressure']['systolic'] ?? $vitals['bloodPressureSystolic'] ?? '',
                                    'diastolic' => $vitals['bloodPressure']['diastolic'] ?? $vitals['bloodPressureDiastolic'] ?? '',
                                ],
                                'pulse' => $vitals['pulse'] ?? $vitals['heartRate'] ?? '',
                                'temperature' => $vitals['temperature'] ?? '',
                                'spo2' => $vitals['spo2'] ?? $vitals['oxygenSaturation'] ?? '',
                            ],
                        ],
                        'diagnoses' => $item->getDiagnoses() ?? [],
                        'medications' => $this->getMedicationsArray($item),
                        'labTests' => $this->getExamsArray($item),
                        'followUp' => $item->getFollowUp() ?? [],
                    ],
                ];
            }
        }

        return $this->render('doctor/clinical-notes.html.twig', [
            'patient' => $patient,
            'patientData' => $patientData,
            'history' => $history,
            'historyData' => $historyData,
            'currentNoteData' => $currentNoteData,
            'currentConsultationId' => $consultationId
        ]);
    }

    /**
     * Helper - Récupérer les médicaments
     */
    private function getMedicationsArray(Consultation $consultation): array
    {
        $ordonnances = $this->em->getRepository(Ordonnance::class)->findBy(['consultation' => $consultation]);
        $medications = [];
        
        foreach ($ordonnances as $ord) {
            $medications[] = [
                'name' => $ord->getMedicament(),
                'dosage' => $ord->getDosage(),
                'form' => $ord->getForme(),
                'frequency' => $ord->getFrequency(),
                'duration' => $ord->getDureeTraitement(),
                'instructions' => $ord->getInstructions()
            ];
        }
        
        return $medications;
    }

    /**
     * Helper - Récupérer les examens
     */
    private function getExamsArray(Consultation $consultation): array
    {
        $examens = $this->em->getRepository(Examens::class)->findBy(['consultation' => $consultation]);
        $exams = [];
        
        foreach ($examens as $exam) {
            $exams[] = [
                'type' => $exam->getTypeExamen(),
                'name' => $exam->getNomExamen(),
                'result' => $exam->getResultat(),
                'status' => $exam->getStatus(),
                'notes' => $exam->getNotes()
            ];
        }
        
        return $exams;
    }

    /**
     * Save Clinical Note
     */
    #[Route('/api/clinical-notes/save', name: 'doctor_save_clinical_note', methods: ['POST'])]
    public function saveClinicalNote(Request $request): JsonResponse
    {   
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!$data || !isset($data['consultation'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Données invalides: consultation manquante'
                ], 400);
            }
            
            $consultation = new Consultation();
            $consultationData = $data['consultation'];
            
            // Set the current user as the doctor
            $currentUser = $this->getUser();
            if ($currentUser instanceof User) {
                $consultation->setMedecin($currentUser);
            }
            
            if (empty($consultationData['chiefComplaint'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Le motif de consultation est obligatoire'
                ], 400);
            }
            
            $consultation->setReasonForVisit($consultationData['chiefComplaint']);
            $consultation->setSubjective($consultationData['subjective'] ?? '');
            $consultation->setObjective($consultationData['objective'] ?? '');
            $consultation->setAssessment($consultationData['assessment'] ?? '');
            $consultation->setPlan($consultationData['plan'] ?? '');
            
            // Set the patient if patientId is provided
            if (!empty($data['patientId'])) {
                $patient = $this->em->getRepository(Patient::class)->findOneBy(['uuid' => $data['patientId']]);
                if ($patient) {
                    $consultation->setPatient($patient);
                }
            }
            
            if (isset($consultationData['vitals']) && is_array($consultationData['vitals'])) {
                $consultation->setVitals($consultationData['vitals']);
            }
            
            $consultation->setDateConsultation(new \DateTime());
            $consultation->setTimeConsultation(new \DateTime());
            $consultation->setConsultationType('soap');
            $consultation->setStatus('completed');
            $consultation->setAppointmentMode('in_person');
            $consultation->setDuration(30);
            $consultation->setFee(0);
            
            $symptoms = ($consultationData['subjective'] ?? '') . "\n" . ($consultationData['objective'] ?? '');
            $consultation->setSymptomsDescription(substr($symptoms, 0, 500));
            $consultation->setLocation('Cabinet');
            $consultation->setNotes($consultationData['plan'] ?? '');
            
            $this->em->persist($consultation);
            
            if (isset($data['diagnoses']) && is_array($data['diagnoses'])) {
                $consultation->setDiagnoses($data['diagnoses']);
            }
            
            if (isset($data['medications']) && is_array($data['medications'])) {
                foreach ($data['medications'] as $medData) {
                    $ordonnance = new Ordonnance();
                    $ordonnance->setConsultation($consultation);
                    $ordonnance->setMedicament($medData['name'] ?? 'Médicament');
                    $ordonnance->setDosage($medData['dosage'] ?? '');
                    $ordonnance->setForme($medData['form'] ?? 'comprimé');
                    $ordonnance->setFrequency($medData['frequency'] ?? '1x/jour');
                    $ordonnance->setDureeTraitement($medData['duration'] ?? '7 jours');
                    $ordonnance->setInstructions($medData['instructions'] ?? '');
                    $ordonnance->setDiagnosisCode($medData['diagnosisCode'] ?? $medData['associatedDiagnosis'] ?? '');
                    $ordonnance->setDateOrdonnance(new \DateTime());
                    
                    // Set the current user as the prescriber
                    if ($currentUser instanceof User) {
                        $ordonnance->setPrescribedBy($currentUser);
                    }
                    
                    $this->em->persist($ordonnance);
                }
            }
            
            if (isset($data['labTests']) && is_array($data['labTests'])) {
                foreach ($data['labTests'] as $examData) {
                    $examen = new Examens();
                    $examen->setConsultation($consultation);
                    $examen->setTypeExamen($examData['type'] ?? 'laboratoire');
                    $examen->setNomExamen($examData['name'] ?? 'Examen');
                    $examen->setDateExamen(new \DateTime());
                    $examen->setResultat($examData['result'] ?? '');
                    $examen->setStatus($examData['status'] ?? 'prescrit');
                    $examen->setNotes($examData['notes'] ?? '');
                    
                    // Set the current user as the prescriber
                    if ($currentUser instanceof User) {
                        $examen->setPrescribedBy($currentUser);
                    }
                    
                    $this->em->persist($examen);
                }
            }
            
            if (isset($data['followUp']) && is_array($data['followUp'])) {
                $consultation->setFollowUp($data['followUp']);
            }
            
            $this->em->flush();
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Note clinique sauvegardée avec succès',
                'consultationId' => $consultation->getId(),
                'date' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update Clinical Note (modify existing)
     */
    #[Route('/api/clinical-notes/update', name: 'doctor_update_clinical_note', methods: ['PUT'])]
    public function updateClinicalNote(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || !isset($data['consultation'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Donn??es invalides: consultation manquante'
                ], 400);
            }

            $noteId = $data['noteId'] ?? $data['consultationId'] ?? null;
            if (!$noteId) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Identifiant de note manquant'
                ], 400);
            }

            $consultation = $this->em->getRepository(Consultation::class)->find($noteId);
            if (!$consultation) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Note non trouv??e'
                ], 404);
            }

            $consultationData = $data['consultation'];

            if (array_key_exists('chiefComplaint', $consultationData)
                && trim((string) $consultationData['chiefComplaint']) === '') {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Le motif de consultation est obligatoire'
                ], 400);
            }

            if (array_key_exists('chiefComplaint', $consultationData)) {
                $consultation->setReasonForVisit($consultationData['chiefComplaint']);
            }

            $subjective = $consultation->getSubjective() ?? '';
            $objective = $consultation->getObjective() ?? '';
            $updateSymptoms = false;

            if (array_key_exists('subjective', $consultationData)) {
                $subjective = $consultationData['subjective'] ?? '';
                $consultation->setSubjective($subjective);
                $updateSymptoms = true;
            }

            if (array_key_exists('objective', $consultationData)) {
                $objective = $consultationData['objective'] ?? '';
                $consultation->setObjective($objective);
                $updateSymptoms = true;
            }

            if (array_key_exists('assessment', $consultationData)) {
                $consultation->setAssessment($consultationData['assessment'] ?? '');
            }

            if (array_key_exists('plan', $consultationData)) {
                $consultation->setPlan($consultationData['plan'] ?? '');
                $consultation->setNotes($consultationData['plan'] ?? '');
            }

            if (isset($consultationData['vitals']) && is_array($consultationData['vitals'])) {
                $consultation->setVitals($consultationData['vitals']);
            }

            if ($updateSymptoms) {
                $symptoms = $subjective . "\n" . $objective;
                $consultation->setSymptomsDescription(substr($symptoms, 0, 500));
            }

            if (array_key_exists('location', $consultationData)) {
                $consultation->setLocation($consultationData['location'] ?? 'Cabinet');
            }

            if (array_key_exists('diagnoses', $data) && is_array($data['diagnoses'])) {
                $consultation->setDiagnoses($data['diagnoses']);
            }

            if (array_key_exists('followUp', $data) && is_array($data['followUp'])) {
                $consultation->setFollowUp($data['followUp']);
            }

            if (array_key_exists('medications', $data) && is_array($data['medications']) && count($data['medications']) > 0) {
                $existingOrdonnances = $this->em->getRepository(Ordonnance::class)->findBy(['consultation' => $consultation]);
                foreach ($existingOrdonnances as $ordonnance) {
                    $this->em->remove($ordonnance);
                }
                foreach ($data['medications'] as $medData) {
                    $ordonnance = new Ordonnance();
                    $ordonnance->setConsultation($consultation);
                    $ordonnance->setMedicament($medData['name'] ?? 'Médicament');
                    $ordonnance->setDosage($medData['dosage'] ?? '');
                    $ordonnance->setForme($medData['form'] ?? 'comprimé');
                    $ordonnance->setFrequency($medData['frequency'] ?? '1x/jour');
                    $ordonnance->setDureeTraitement($medData['duration'] ?? '7 jours');
                    $ordonnance->setInstructions($medData['instructions'] ?? '');
                    $ordonnance->setDiagnosisCode($medData['diagnosisCode'] ?? $medData['associatedDiagnosis'] ?? '');
                    $ordonnance->setDateOrdonnance(new \DateTime());
                    
                    // Set the current user as the prescriber
                    if ($this->getUser() instanceof User) {
                        $ordonnance->setPrescribedBy($this->getUser());
                    }
                    
                    $this->em->persist($ordonnance);
                }
            }

            if (array_key_exists('labTests', $data) && is_array($data['labTests']) && count($data['labTests']) > 0) {
                $existingExams = $this->em->getRepository(Examens::class)->findBy(['consultation' => $consultation]);
                foreach ($existingExams as $examen) {
                    $this->em->remove($examen);
                }
                foreach ($data['labTests'] as $examData) {
                    $examen = new Examens();
                    $examen->setConsultation($consultation);
                    $examen->setTypeExamen($examData['type'] ?? 'laboratoire');
                    $examen->setNomExamen($examData['name'] ?? 'Examen');
                    $examen->setDateExamen(new \DateTime());
                    $examen->setResultat($examData['result'] ?? '');
                    $examen->setStatus($examData['status'] ?? 'prescrit');
                    $examen->setNotes($examData['notes'] ?? '');
                    
                    // Set the current user as the prescriber
                    if ($this->getUser() instanceof User) {
                        $examen->setPrescribedBy($this->getUser());
                    }
                    
                    $this->em->persist($examen);
                }
            }

            $this->em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Note clinique modifi??e avec succ??s',
                'consultationId' => $consultation->getId(),
                'date' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Clinical Note
     */
    #[Route('/api/clinical-notes/delete/{id}', name: 'api_clinical_note_delete', methods: ['DELETE'])]
    public function apiDeleteClinicalNote(int $id): JsonResponse
    {
        try {
            $consultation = $this->em->getRepository(Consultation::class)->find($id);
            if (!$consultation) {
                return new JsonResponse(['success' => false, 'message' => 'Note non trouvée'], 404);
            }

            $ordonnances = $this->em->getRepository(Ordonnance::class)->findBy(['consultation' => $consultation]);
            foreach ($ordonnances as $ordonnance) {
                $this->em->remove($ordonnance);
            }
            
            $examens = $this->em->getRepository(Examens::class)->findBy(['consultation' => $consultation]);
            foreach ($examens as $examen) {
                $this->em->remove($examen);
            }
            
            $this->em->remove($consultation);
            $this->em->flush();

            return new JsonResponse(['success' => true, 'message' => 'Note supprimée avec succès']);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /*******************************************************************
     * GESTION DES CONSULTATIONS (CRUD COMPLET)
     *******************************************************************/

    #[Route('/consultation', name: 'doctor_consultation_list', methods: ['GET'])]
    public function consultationList(): Response
    {
        $consultations = $this->em->getRepository(Consultation::class)->findBy([], ['date_consultation' => 'DESC']);
        
        return $this->render('doctor/consultation/list.html.twig', [
            'consultations' => $consultations,
        ]);
    }

    #[Route('/consultation/new', name: 'doctor_consultation_new', methods: ['GET', 'POST'])]
    public function newConsultation(Request $request): Response
    {
        $consultation = new Consultation();
        $form = $this->createForm(SoapType::class, $consultation);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$consultation->getDateConsultation()) {
                $consultation->setDateConsultation(new \DateTime());
            }
            if (!$consultation->getTimeConsultation()) {
                $consultation->setTimeConsultation(new \DateTime());
            }
            if (!$consultation->getConsultationType()) {
                $consultation->setConsultationType('soap');
            }
            if (!$consultation->getStatus()) {
                $consultation->setStatus('completed');
            }
            if (!$consultation->getAppointmentMode()) {
                $consultation->setAppointmentMode('in_person');
            }
            if (!$consultation->getDuration()) {
                $consultation->setDuration(30);
            }
            if (!$consultation->getFee()) {
                $consultation->setFee(0);
            }
            
            $this->em->persist($consultation);
            $this->em->flush();
            
            $this->addFlash('success', 'Consultation créée avec succès');
            return $this->redirectToRoute('doctor_consultation_list');
        }
        
        return $this->render('doctor/consultation/new.html.twig', [
            'form' => $form->createView(),
            'consultation' => $consultation,
        ]);
    }

    #[Route('/consultation/{id}/edit', name: 'doctor_consultation_edit', methods: ['GET', 'POST'])]
    public function editConsultation(Request $request, Consultation $consultation): Response
    {
        $form = $this->createForm(SoapType::class, $consultation);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $consultation->setUpdatedAt(new \DateTime());
            
            $this->em->flush();
            
            $this->addFlash('success', 'Consultation modifiée avec succès');
            return $this->redirectToRoute('doctor_consultation_list');
        }
        
        return $this->render('doctor/consultation/edit.html.twig', [
            'form' => $form->createView(),
            'consultation' => $consultation,
        ]);
    }

    /**
     * Delete Consultation (API)
     */
    #[Route('/api/consultation/{id}', name: 'doctor_api_delete_consultation', methods: ['DELETE'])]
    public function deleteConsultationApi(int $id): JsonResponse
    {
        try {
            $consultation = $this->em->getRepository(Consultation::class)->find($id);
            
            if (!$consultation) {
                return $this->json([
                    'success' => false,
                    'message' => 'Consultation non trouvée',
                ], 404);
            }

            // Delete consultation (all related records will be cascade deleted by the database)
            $this->em->remove($consultation);
            $this->em->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Consultation supprimée avec succès',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la consultation',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/consultation/{id}/delete', name: 'doctor_consultation_delete', methods: ['POST'])]
    public function deleteConsultation(Request $request, Consultation $consultation): RedirectResponse
    {
        if ($this->isCsrfTokenValid('delete' . $consultation->getId(), $request->request->get('_token'))) {
            $ordonnances = $this->em->getRepository(Ordonnance::class)->findBy(['consultation' => $consultation]);
            foreach ($ordonnances as $ordonnance) {
                $this->em->remove($ordonnance);
            }
            
            $examens = $this->em->getRepository(Examens::class)->findBy(['consultation' => $consultation]);
            foreach ($examens as $examen) {
                $this->em->remove($examen);
            }
            
            $this->em->remove($consultation);
            $this->em->flush();
            
            $this->addFlash('success', 'Consultation supprimée avec succès');
        }
        
        return $this->redirectToRoute('doctor_consultation_list');
    }

    #[Route('/consultation/{id}/show', name: 'doctor_consultation_show', methods: ['GET'])]
    public function showConsultation(Consultation $consultation): Response
    {
        $ordonnances = $this->em->getRepository(Ordonnance::class)->findBy(['consultation' => $consultation]);
        $examens = $this->em->getRepository(Examens::class)->findBy(['consultation' => $consultation]);
        
        return $this->render('doctor/consultation/show.html.twig', [
            'consultation' => $consultation,
            'ordonnances' => $ordonnances,
            'examens' => $examens,
        ]);
    }

    #[Route('/patient/{id}/communication', name: 'doctor_patient_communication', methods: ['GET'])]
    public function patientCommunication(int $id): Response
    {
        $patient = $this->em->getRepository(Patient::class)->find($id);
        
        if (!$patient) {
            $this->addFlash('error', 'Patient non trouvé');
            return $this->redirectToRoute('doctor_patients');
        }

        return $this->render('doctor/patient-communication.html.twig', [
            'patient' => $patient
        ]);
    }

    #[Route('/patient-queue', name: 'doctor_patient_queue_page', methods: ['GET'])]
    public function patientQueue(): Response
    {
        $todayStart = (new \DateTime())->setTime(0, 0, 0);
        $todayEnd = (new \DateTime())->setTime(23, 59, 59);
        
        $todaysConsultations = $this->em->getRepository(Consultation::class)->createQueryBuilder('c')
            ->where('c.date_consultation >= :today_start')
            ->andWhere('c.date_consultation <= :today_end')
            ->andWhere('c.status IN (:statuses)')
            ->setParameter('today_start', $todayStart)
            ->setParameter('today_end', $todayEnd)
            ->setParameter('statuses', ['scheduled', 'in_progress', 'waiting'])
            ->orderBy('c.date_consultation', 'ASC')
            ->getQuery()
            ->getResult();

        $queueData = [];
        foreach ($todaysConsultations as $consultation) {
            if (method_exists($consultation, 'getIdPatient')) {
                $patient = $consultation->getIdPatient();
            } else {
                $patient = null;
            }
            $queueData[] = [
                'id' => $consultation->getId(),
                'patientName' => $patient ? $patient->getNom() . ' ' . $patient->getPrenom() : 'Patient inconnu',
                'appointmentTime' => $consultation->getTimeConsultation() ? $consultation->getTimeConsultation()->format('H:i') : '',
                'status' => $consultation->getStatus(),
                'reason' => $consultation->getReasonForVisit(),
                'waitTime' => '15 min',
            ];
        }

        return $this->render('doctor/patient-queue.html.twig', [
            'queue' => $queueData,
        ]);
    }

    #[Route('/availability-settings', name: 'doctor_availability_settings', methods: ['GET'])]
    public function availabilitySettings(): Response
    {
        $availability = [
            'monday' => ['09:00', '12:00', '14:00', '17:00'],
            'tuesday' => ['09:00', '12:00', '14:00', '17:00'],
            'wednesday' => ['09:00', '12:00'],
            'thursday' => ['09:00', '12:00', '14:00', '17:00'],
            'friday' => ['09:00', '12:00', '14:00', '16:00'],
        ];

        return $this->render('doctor/availability-settings.html.twig', [
            'availability' => $availability,
        ]);
    }

    #[Route('/availability/settings/load', name: 'doctor_availability_settings_load', methods: ['GET'])]
    public function loadAvailabilitySettings(): JsonResponse
    {
        try {
            $user = $this->getUser();
            
            // Check if user is a Medecin
            if (!$user instanceof Medecin) {
                return $this->json([
                    'success' => false,
                    'error' => 'Utilisateur non autorisé',
                ], 403);
            }
            
            // Get user UUID
            $userUuid = $user->getUuid();
            
            if (!$userUuid) {
                return $this->json([
                    'success' => false,
                    'error' => 'UUID non trouvé',
                ], 400);
            }
        
        // Default settings
        $settings = [
            'defaultAppointmentDuration' => '30',
            'appointmentGap' => '5',
            'lunchBreakStart' => '12:00',
            'lunchBreakEnd' => '13:00',
            'emergencySlotsPerDay' => 2,
            'allowDoubleBooking' => false,
            'autoConfirmAppointments' => false,
        ];
        
        // Load weekly schedule from database using UUID
        $availabilities = $this->em->getRepository(DoctorAvailability::class)->findByMedecinUuid($userUuid);
        
        $daysMap = [
            'monday' => ['name' => 'Lundi', 'key' => 'monday'],
            'tuesday' => ['name' => 'Mardi', 'key' => 'tuesday'],
            'wednesday' => ['name' => 'Mercredi', 'key' => 'wednesday'],
            'thursday' => ['name' => 'Jeudi', 'key' => 'thursday'],
            'friday' => ['name' => 'Vendredi', 'key' => 'friday'],
            'saturday' => ['name' => 'Samedi', 'key' => 'saturday'],
            'sunday' => ['name' => 'Dimanche', 'key' => 'sunday'],
        ];
        
        // Create a map of existing availabilities
        $availabilityMap = [];
        foreach ($availabilities as $availability) {
            $availabilityMap[$availability->getDayOfWeek()] = $availability;
        }
        
        // Build weekly schedule
        $weeklySchedule = [];
        foreach ($daysMap as $key => $dayInfo) {
            if (isset($availabilityMap[$key])) {
                $avail = $availabilityMap[$key];
                $weeklySchedule[] = [
                    'name' => $dayInfo['name'],
                    'key' => $key,
                    'isActive' => $avail->isActive(),
                    'startTime' => $avail->getStartTime(),
                    'endTime' => $avail->getEndTime(),
                    'location' => $avail->getLocation() ?? 'clinic',
                    'slots' => [],
                ];
            } else {
                // Default values
                $isActive = in_array($key, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
                $weeklySchedule[] = [
                    'name' => $dayInfo['name'],
                    'key' => $key,
                    'isActive' => $isActive,
                    'startTime' => '08:00',
                    'endTime' => '18:00',
                    'location' => 'clinic',
                    'slots' => [],
                ];
            }
        }
        
        // Load locations from database using UUID
        $locationEntities = $this->em->getRepository(DoctorLocation::class)->findByMedecinUuid($userUuid);
        $locations = [];
        foreach ($locationEntities as $loc) {
            $locations[] = [
                'id' => 'loc' . $loc->getId(),
                'name' => $loc->getName(),
                'type' => $loc->getType(),
                'address' => $loc->getAddress(),
                'phone' => $loc->getPhone(),
                'isActive' => $loc->isActive(),
            ];
        }
        
        // If no locations, add default
        if (empty($locations)) {
            $locations[] = [
                'id' => 'loc1',
                'name' => 'Cabinet Principal',
                'type' => 'clinic',
                'address' => '',
                'phone' => '',
                'isActive' => true,
            ];
        }
        
        // Load leaves from database using UUID
        $leaveEntities = $this->em->getRepository(DoctorLeave::class)->findUpcomingByMedecinUuid($userUuid);
        $leaves = [];
        foreach ($leaveEntities as $leave) {
            $leaves[] = [
                'id' => (string) $leave->getId(),
                'type' => $leave->getType(),
                'title' => $leave->getTitle(),
                'startDate' => $leave->getStartDate()?->format('Y-m-d'),
                'endDate' => $leave->getEndDate()?->format('Y-m-d'),
                'days' => $leave->getDaysCount(),
                'reason' => $leave->getReason(),
                'status' => $leave->getStatus(),
            ];
        }
        
        return $this->json([
            'success' => true,
            'settings' => $settings,
            'weeklySchedule' => $weeklySchedule,
            'locations' => $locations,
            'leaves' => $leaves,
        ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/availability/settings/save', name: 'doctor_availability_settings_save', methods: ['POST'])]
    public function saveAvailabilitySettings(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Check if user is a Medecin
        if (!$user instanceof Medecin) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur non autorisé',
            ], 403);
        }
        
        $userUuid = $user->getUuid();
        $data = json_decode($request->getContent(), true);
        
        // Save weekly schedule
        if (isset($data['weeklySchedule'])) {
            foreach ($data['weeklySchedule'] as $dayData) {
                $availability = $this->em->getRepository(DoctorAvailability::class)
                    ->findByMedecinUuidAndDay($userUuid, $dayData['key']);
                
                if (!$availability) {
                    $availability = new DoctorAvailability();
                    $availability->setMedecin($user);
                    $availability->setDayOfWeek($dayData['key']);
                }
                
                $availability->setIsActive($dayData['isActive'] ?? false);
                $availability->setStartTime($dayData['startTime'] ?? '08:00');
                $availability->setEndTime($dayData['endTime'] ?? '18:00');
                $availability->setLocation($dayData['location'] ?? null);
                $availability->setUpdatedAt(new \DateTime());
                
                $this->em->persist($availability);
            }
        }
        
        $this->em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Paramètres enregistrés avec succès',
        ]);
    }

    #[Route('/availability/location/add', name: 'doctor_availability_location_add', methods: ['POST'])]
    public function addLocation(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Check if user is a Medecin
        if (!$user instanceof Medecin) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur non autorisé',
            ], 403);
        }
        
        $data = json_decode($request->getContent(), true);
        
        $location = new DoctorLocation();
        $location->setMedecin($user);
        $location->setName($data['name'] ?? '');
        $location->setType($data['type'] ?? 'clinic');
        $location->setAddress($data['address'] ?? null);
        $location->setPhone($data['phone'] ?? null);
        $location->setIsActive(true);
        
        $this->em->persist($location);
        $this->em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Lieu ajouté avec succès',
            'location' => [
                'id' => 'loc' . $location->getId(),
                'name' => $location->getName(),
                'type' => $location->getType(),
                'address' => $location->getAddress(),
                'phone' => $location->getPhone(),
                'isActive' => $location->isActive(),
            ],
        ]);
    }

    #[Route('/availability/leave/request', name: 'doctor_availability_leave_request', methods: ['POST'])]
    public function requestLeave(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Check if user is a Medecin
        if (!$user instanceof Medecin) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur non autorisé',
            ], 403);
        }
        
        $data = json_decode($request->getContent(), true);
        
        $leave = new DoctorLeave();
        $leave->setMedecin($user);
        $leave->setType($data['type'] ?? DoctorLeave::TYPE_VACATION);
        $leave->setTitle($data['title'] ?? '');
        
        if (isset($data['startDate'])) {
            $leave->setStartDate(new \DateTime($data['startDate']));
        }
        if (isset($data['endDate'])) {
            $leave->setEndDate(new \DateTime($data['endDate']));
        }
        
        $leave->setReason($data['reason'] ?? null);
        $leave->setStatus(DoctorLeave::STATUS_PENDING);
        
        $this->em->persist($leave);
        $this->em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Demande de congé soumise avec succès',
            'leave' => [
                'id' => (string) $leave->getId(),
                'type' => $leave->getType(),
                'title' => $leave->getTitle(),
                'startDate' => $leave->getStartDate()?->format('Y-m-d'),
                'endDate' => $leave->getEndDate()?->format('Y-m-d'),
                'days' => $leave->getDaysCount(),
                'reason' => $leave->getReason(),
                'status' => $leave->getStatus(),
            ],
        ]);
    }

    #[Route('/availability/leave/{id}/cancel', name: 'doctor_availability_leave_cancel', methods: ['POST'])]
    public function cancelLeave(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        // Check if user is a Medecin
        if (!$user instanceof Medecin) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur non autorisé',
            ], 403);
        }
        
        $leave = $this->em->getRepository(DoctorLeave::class)->find($id);
        
        if (!$leave) {
            return $this->json([
                'success' => false,
                'message' => 'Demande de congé non trouvée',
            ], 404);
        }
        
        if ($leave->getMedecin()->getId() !== $user->getId()) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }
        
        $leave->setStatus(DoctorLeave::STATUS_CANCELLED);
        $leave->setUpdatedAt(new \DateTime());
        
        $this->em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Demande de congé annulée',
        ]);
    }

    /*******************************************************************
     * SUBSTITUTION (MÉDECIN REMPLAÇANT)
     *******************************************************************/

    #[Route('/availability/substitutes', name: 'doctor_availability_substitutes', methods: ['GET'])]
    public function getAvailableSubstitutes(): JsonResponse
    {
        $user = $this->getUser();
        
        // Check if user is a Medecin
        if (!$user instanceof Medecin) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur non autorisé',
            ], 403);
        }
        
        $userUuid = $user->getUuid();
        $substitutes = $this->em->getRepository(DoctorSubstitution::class)->findAvailableSubstitutesByUuid($userUuid);
        
        $data = [];
        foreach ($substitutes as $substitute) {
            $data[] = [
                'uuid' => $substitute->getUuid(),
                'firstName' => $substitute->getFirstName(),
                'lastName' => $substitute->getLastName(),
                'specialite' => $substitute->getSpecialite(),
                'email' => $substitute->getEmail(),
            ];
        }
        
        return $this->json([
            'success' => true,
            'substitutes' => $data,
        ]);
    }

    #[Route('/availability/substitution/assign', name: 'doctor_availability_substitution_assign', methods: ['POST'])]
    public function assignSubstitute(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Check if user is a Medecin
        if (!$user instanceof Medecin) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur non autorisé',
            ], 403);
        }
        
        $data = json_decode($request->getContent(), true);
        
        $substituteUuid = $data['substituteUuid'] ?? null;
        $leaveId = $data['leaveId'] ?? null;
        $startDate = $data['startDate'] ?? null;
        $endDate = $data['endDate'] ?? null;
        $notes = $data['notes'] ?? null;
        
        if (!$substituteUuid) {
            return $this->json([
                'success' => false,
                'message' => 'Veuillez sélectionner un médecin remplaçant',
            ], 400);
        }
        
        $substitute = $this->em->getRepository(Medecin::class)->findOneBy(['uuid' => $substituteUuid]);
        
        if (!$substitute) {
            return $this->json([
                'success' => false,
                'message' => 'Médecin remplaçant non trouvé',
            ], 404);
        }
        
        $substitution = new DoctorSubstitution();
        $substitution->setMedecin($user);
        $substitution->setSubstitute($substitute);
        
        if ($leaveId) {
            $leave = $this->em->getRepository(DoctorLeave::class)->find($leaveId);
            if ($leave) {
                $substitution->setLeave($leave);
            }
        }
        
        if ($startDate) {
            $substitution->setStartDate(new \DateTime($startDate));
        }
        if ($endDate) {
            $substitution->setEndDate(new \DateTime($endDate));
        }
        
        $substitution->setNotes($notes);
        $substitution->setStatus(DoctorSubstitution::STATUS_PENDING);
        
        $this->em->persist($substitution);
        $this->em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Demande de remplacement envoyée avec succès',
            'substitution' => [
                'id' => $substitution->getId(),
                'substituteName' => $substitute->getFirstName() . ' ' . $substitute->getLastName(),
                'startDate' => $substitution->getStartDate()?->format('Y-m-d'),
                'endDate' => $substitution->getEndDate()?->format('Y-m-d'),
                'status' => $substitution->getStatus(),
            ],
        ]);
    }

    #[Route('/availability/substitution/{id}/cancel', name: 'doctor_availability_substitution_cancel', methods: ['POST'])]
    public function cancelSubstitution(int $id): JsonResponse
    {
        $user = $this->getUser();
        
        // Check if user is a Medecin
        if (!$user instanceof Medecin) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur non autorisé',
            ], 403);
        }
        
        $substitution = $this->em->getRepository(DoctorSubstitution::class)->find($id);
        
        if (!$substitution) {
            return $this->json([
                'success' => false,
                'message' => 'Demande de remplacement non trouvée',
            ], 404);
        }
        
        if ($substitution->getMedecin()->getUuid() !== $user->getUuid()) {
            return $this->json([
                'success' => false,
                'message' => 'Non autorisé',
            ], 403);
        }
        
        $substitution->setStatus(DoctorSubstitution::STATUS_REJECTED);
        $substitution->setUpdatedAt(new \DateTime());
        
        $this->em->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Demande de remplacement annulée',
        ]);
    }

    #[Route('/availability/substitutions', name: 'doctor_availability_substitutions', methods: ['GET'])]
    public function getSubstitutions(): JsonResponse
    {
        $user = $this->getUser();
        
        // Check if user is a Medecin
        if (!$user instanceof Medecin) {
            return $this->json([
                'success' => false,
                'error' => 'Utilisateur non autorisé',
            ], 403);
        }
        
        $userUuid = $user->getUuid();
        $substitutions = $this->em->getRepository(DoctorSubstitution::class)->findByMedecinUuid($userUuid);
        
        $data = [];
        foreach ($substitutions as $sub) {
            $substitute = $sub->getSubstitute();
            $data[] = [
                'id' => $sub->getId(),
                'substituteUuid' => $substitute?->getUuid(),
                'substituteName' => $substitute ? $substitute->getFirstName() . ' ' . $substitute->getLastName() : 'N/A',
                'startDate' => $sub->getStartDate()?->format('Y-m-d'),
                'endDate' => $sub->getEndDate()?->format('Y-m-d'),
                'status' => $sub->getStatus(),
                'notes' => $sub->getNotes(),
            ];
        }
        
        return $this->json([
            'success' => true,
            'substitutions' => $data,
        ]);
    }
}
