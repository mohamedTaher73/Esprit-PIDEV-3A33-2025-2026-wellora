<?php

namespace App\Controller\Admin;

use App\Entity\ProfessionalVerification;
use App\Entity\User;
use App\Repository\ProfessionalVerificationRepository;
use App\Service\DiplomaVerificationService;
use App\Service\ProfessionalVerificationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/verification')]
#[IsGranted('ROLE_ADMIN')]
class ProfessionalVerificationController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private ProfessionalVerificationRepository $verificationRepository;
    private DiplomaVerificationService $verificationService;
    private ProfessionalVerificationEmailService $emailService;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProfessionalVerificationRepository $verificationRepository,
        DiplomaVerificationService $verificationService,
        ProfessionalVerificationEmailService $emailService
    ) {
        $this->entityManager = $entityManager;
        $this->verificationRepository = $verificationRepository;
        $this->verificationService = $verificationService;
        $this->emailService = $emailService;
    }

    /**
     * Get User from UUID
     */
    private function getUserFromUuid(string $uuid): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['uuid' => $uuid]);
    }

    /**
     * Dashboard - Show all verifications with filters
     */
    #[Route('/', name: 'admin_verification_dashboard')]
    public function dashboard(Request $request): Response
    {
        $status = $request->query->get('status', 'all');
        
        $verifications = match($status) {
            'pending' => $this->verificationRepository->findPendingVerifications(),
            'manual_review' => $this->verificationRepository->findManualReviewVerifications(),
            'verified' => $this->verificationRepository->findBy(['status' => ProfessionalVerification::STATUS_VERIFIED]),
            'rejected' => $this->verificationRepository->findBy(['status' => ProfessionalVerification::STATUS_REJECTED]),
            default => $this->verificationRepository->findRecentVerifications(50),
        };

        $statistics = $this->verificationRepository->getStatistics();

        // Build array with user data for each verification
        $verificationsWithUsers = [];
        foreach ($verifications as $verification) {
            $user = null;
            if ($verification->getProfessionalUuid()) {
                $user = $this->getUserFromUuid($verification->getProfessionalUuid());
            }
            $verificationsWithUsers[] = [
                'verification' => $verification,
                'user' => $user,
            ];
        }

        return $this->render('admin/verification/dashboard.html.twig', [
            'verificationsWithUsers' => $verificationsWithUsers,
            'statistics' => $statistics,
            'current_status' => $status,
        ]);
    }

    /**
     * View verification details
     */
    #[Route('/view/{id}', name: 'admin_verification_view')]
    public function view(int $id): Response
    {
        $verification = $this->verificationRepository->find($id);
        
        if (!$verification) {
            $this->addFlash('error', 'Vérification non trouvée');
            return $this->redirectToRoute('admin_verification_dashboard');
        }

        // Get user from UUID
        $professional = null;
        if ($verification->getProfessionalUuid()) {
            $professional = $this->getUserFromUuid($verification->getProfessionalUuid());
        }

        return $this->render('admin/verification/view.html.twig', [
            'verification' => $verification,
            'professional' => $professional,
            'diploma_url' => $verification->getDiplomaPath() 
                ? $this->verificationService->getDiplomaUrl($verification->getDiplomaPath())
                : null,
        ]);
    }

    /**
     * Reprocess a verification (trigger AI verification)
     */
    #[Route('/reprocess/{id}', name: 'admin_verification_reprocess')]
    public function reprocess(int $id): Response
    {
        $verification = $this->verificationRepository->find($id);
        
        if (!$verification) {
            $this->addFlash('error', 'Vérification non trouvée');
            return $this->redirectToRoute('admin_verification_dashboard');
        }

        try {
            // Reprocess the verification
            $verification = $this->verificationService->processVerification($verification);
            $this->entityManager->flush();

            $score = $verification->getConfidenceScore();
            $this->addFlash('success', 'Vérification traitée. Score: ' . ($score !== null ? $score : 'N/A') . '/100');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors du traitement: ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_verification_view', ['id' => $id]);
    }

    /**
     * Manual approve verification
     */
    #[Route('/approve/{id}', name: 'admin_verification_approve')]
    public function approve(int $id, Request $request): Response
    {
        $verification = $this->verificationRepository->find($id);
        
        if (!$verification) {
            $this->addFlash('error', 'Vérification non trouvée');
            return $this->redirectToRoute('admin_verification_dashboard');
        }

        // Validate CSRF token
        $csrfToken = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('approve_verification_' . $id, $csrfToken)) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('admin_verification_view', ['id' => $id]);
        }

        $verification->setStatus(ProfessionalVerification::STATUS_VERIFIED);
        $verification->setVerifiedAt(new \DateTime());
        $verification->setReviewedBy($this->getUser()->getEmail());
        $verification->setConfidenceScore(100); // Manual approval overrides score
        $verification->setRejectionReason($request->request->get('reason', 'Approuvé manuellement par l\'administrateur'));

        // Update user's verification status using UUID
        if ($verification->getProfessionalUuid()) {
            $user = $this->getUserFromUuid($verification->getProfessionalUuid());
            if ($user && method_exists($user, 'setVerifiedByAdmin')) {
                $user->setVerifiedByAdmin(true);
                $user->setVerificationDate(new \DateTime());
            }
        }

        $this->entityManager->flush();

        // Send approval email
        $emailSent = $this->emailService->sendApprovalEmail($verification);
        if ($emailSent) {
            $this->addFlash('success', 'Professionnel approuvé avec succès - Email de confirmation envoyé');
        } else {
            $this->addFlash('warning', 'Professionnel approuvé mais l\'email n\'a pas pu être envoyé');
        }
        
        return $this->redirectToRoute('admin_verification_dashboard', ['status' => 'manual_review']);
    }

    /**
     * Manual reject verification
     */
    #[Route('/reject/{id}', name: 'admin_verification_reject')]
    public function reject(int $id, Request $request): Response
    {
        $verification = $this->verificationRepository->find($id);
        
        if (!$verification) {
            $this->addFlash('error', 'Vérification non trouvée');
            return $this->redirectToRoute('admin_verification_dashboard');
        }

        // Validate CSRF token
        $csrfToken = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('reject_verification_' . $id, $csrfToken)) {
            $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('admin_verification_view', ['id' => $id]);
        }

        $reason = $request->request->get('reason');
        
        if (empty($reason)) {
            $this->addFlash('error', 'Vous devez fournir une raison pour le rejet');
            return $this->redirectToRoute('admin_verification_view', ['id' => $id]);
        }

        $verification->setStatus(ProfessionalVerification::STATUS_REJECTED);
        $verification->setVerifiedAt(new \DateTime());
        $verification->setReviewedBy($this->getUser()->getEmail());
        $verification->setConfidenceScore(0);
        $verification->setRejectionReason($reason);

        // Disable the professional's account
        if ($verification->getProfessionalUuid()) {
            $user = $this->getUserFromUuid($verification->getProfessionalUuid());
            if ($user) {
                $user->setIsActive(false);
                $user->setVerifiedByAdmin(false);
            }
        }

        $this->entityManager->flush();

        // Send rejection email
        $this->emailService->sendRejectionEmail($verification, $reason);

        $this->addFlash('success', 'Professionnel rejeté');
        
        return $this->redirectToRoute('admin_verification_dashboard', ['status' => 'manual_review']);
    }

    /**
     * Statistics API endpoint
     */
    #[Route('/statistics', name: 'admin_verification_statistics')]
    public function statistics(): Response
    {
        $statistics = $this->verificationRepository->getStatistics();
        
        return $this->json($statistics);
    }

    /**
     * Process all pending verifications
     */
    #[Route('/process-all', name: 'admin_verification_process_all')]
    public function processAll(): Response
    {
        $pendingVerifications = $this->verificationRepository->findPendingVerifications();
        
        $processed = 0;
        $approved = 0;
        $rejected = 0;
        $manualReview = 0;

        foreach ($pendingVerifications as $verification) {
            try {
                $verification = $this->verificationService->processVerification($verification);
                $this->entityManager->flush();
                
                $processed++;
                
                if ($verification->getStatus() === ProfessionalVerification::STATUS_VERIFIED) {
                    $approved++;
                } elseif ($verification->getStatus() === ProfessionalVerification::STATUS_REJECTED) {
                    $rejected++;
                } else {
                    $manualReview++;
                }
            } catch (\Exception $e) {
                // Log error but continue
            }
        }

        $this->addFlash('success', 
            "Traitement terminé: {$processed} traités, {$approved} approuvés, {$rejected} rejetés, {$manualReview} en révision"
        );

        return $this->redirectToRoute('admin_verification_dashboard');
    }
}
