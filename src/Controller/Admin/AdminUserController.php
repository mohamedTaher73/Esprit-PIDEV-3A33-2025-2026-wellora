<?php

namespace App\Controller\Admin;

use App\Entity\Administrator;
use App\Entity\Coach;
use App\Entity\Medecin;
use App\Entity\Nutritionist;
use App\Entity\Patient;
use App\Entity\User;
use App\Enum\UserRole;
use App\Form\Admin\UserEditFormType;
use App\Form\Admin\UserFilterFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator
    ) {}

    /**
     * List all users with filters
     */
    #[Route('/', name: 'admin_users_index', methods: ['GET'])]
    #[Template('admin/users/index.html.twig')]
    public function index(Request $request): array
    {
        $filters = [
            'role' => $request->query->get('role', 'all'),
            'search' => $request->query->get('search', ''),
            'isActive' => $request->query->get('isActive', ''),
            'isVerified' => $request->query->get('isVerified', ''),
        ];

        $users = $this->userRepository->findAllWithFilters(
            $filters['role'] !== 'all' ? $filters['role'] : null,
            $filters['search'] ?: null,
            $filters['isActive'] !== '' ? (bool) $filters['isActive'] : null,
            $filters['isVerified'] !== '' ? (bool) $filters['isVerified'] : null
        );

        $statistics = $this->userRepository->getUserStatistics();

        return [
            'users' => $users,
            'filters' => $filters,
            'statistics' => $statistics,
        ];
    }

    /**
     * Show user details
     */
    #[Route('/{uuid}', name: 'admin_users_show', methods: ['GET'])]
    #[Template('admin/users/show.html.twig')]
    public function show(string $uuid): array
    {
        $user = $this->userRepository->findOneByUuid($uuid);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        return [
            'user' => $user,
        ];
    }

    /**
     * Edit user
     */
    #[Route('/{uuid}/edit', name: 'admin_users_edit', methods: ['GET', 'POST'])]
    #[Template('admin/users/edit.html.twig')]
    public function edit(Request $request, string $uuid): array|Response
    {
        $user = $this->userRepository->findOneByUuid($uuid);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $form = $this->createForm(UserEditFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans('admin.users.edit.success'));

            return $this->redirectToRoute('admin_users_show', ['uuid' => $uuid]);
        }

        return [
            'user' => $user,
            'form' => $form->createView(),
        ];
    }

    /**
     * Toggle user active status
     */
    #[Route('/{uuid}/toggle-active', name: 'admin_users_toggle_active', methods: ['POST'])]
    public function toggleActive(Request $request, string $uuid): Response
    {
        $user = $this->userRepository->findOneByUuid($uuid);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        if (!$this->isCsrfTokenValid('toggle-active-' . $uuid, $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('admin.csrf_invalid'));
            
            return $this->redirectToRoute('admin_users_index');
        }

        $user->setIsActive(!$user->isIsActive());
        $this->entityManager->flush();

        $status = $user->isIsActive() ? 'activated' : 'deactivated';
        $this->addFlash('success', $this->translator->trans("admin.users.toggle_active.$status", ['%email%' => $user->getEmail()]));

        return $this->redirectToRoute('admin_users_index');
    }

    /**
     * Verify professional (license and diploma)
     */
    #[Route('/{uuid}/verify', name: 'admin_users_verify', methods: ['POST'])]
    public function verify(Request $request, string $uuid): Response
    {
        $user = $this->userRepository->findOneByUuid($uuid);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Only professionals can be verified
        if (!$user instanceof Medecin && !$user instanceof Coach && !$user instanceof Nutritionist) {
            $this->addFlash('error', $this->translator->trans('admin.users.verify.only_professionals'));
            
            return $this->redirectToRoute('admin_users_show', ['uuid' => $uuid]);
        }

        if (!$this->isCsrfTokenValid('verify-' . $uuid, $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('admin.csrf_invalid'));
            
            return $this->redirectToRoute('admin_users_show', ['uuid' => $uuid]);
        }

        $user->setVerifiedByAdmin(true);
        $user->setVerificationDate(new \DateTime());
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('admin.users.verify.success', ['%email%' => $user->getEmail()]));

        return $this->redirectToRoute('admin_users_show', ['uuid' => $uuid]);
    }

    /**
     * Unverify professional
     */
    #[Route('/{uuid}/unverify', name: 'admin_users_unverify', methods: ['POST'])]
    public function unverify(Request $request, string $uuid): Response
    {
        $user = $this->userRepository->findOneByUuid($uuid);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Only professionals can be unverified
        if (!$user instanceof Medecin && !$user instanceof Coach && !$user instanceof Nutritionist) {
            $this->addFlash('error', $this->translator->trans('admin.users.verify.only_professionals'));
            
            return $this->redirectToRoute('admin_users_show', ['uuid' => $uuid]);
        }

        if (!$this->isCsrfTokenValid('unverify-' . $uuid, $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('admin.csrf_invalid'));
            
            return $this->redirectToRoute('admin_users_show', ['uuid' => $uuid]);
        }

        $user->setVerifiedByAdmin(false);
        $user->setVerificationDate(null);
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('admin.users.unverify.success', ['%email%' => $user->getEmail()]));

        return $this->redirectToRoute('admin_users_show', ['uuid' => $uuid]);
    }

    /**
     * Delete user
     */
    #[Route('/{uuid}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(Request $request, string $uuid): Response
    {
        $user = $this->userRepository->findOneByUuid($uuid);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Prevent deleting yourself
        if ($user->getUserIdentifier() === $this->getUser()->getUserIdentifier()) {
            $this->addFlash('error', $this->translator->trans('admin.users.delete.self'));
            
            return $this->redirectToRoute('admin_users_index');
        }

        // Prevent deleting other admins
        if ($user instanceof Administrator) {
            $this->addFlash('error', $this->translator->trans('admin.users.delete.admin'));
            
            return $this->redirectToRoute('admin_users_index');
        }

        if (!$this->isCsrfTokenValid('delete-' . $uuid, $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('admin.csrf_invalid'));
            
            return $this->redirectToRoute('admin_users_index');
        }

        $email = $user->getEmail();
        
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('admin.users.delete.success', ['%email%' => $email]));

        return $this->redirectToRoute('admin_users_index');
    }

    /**
     * List unverified professionals
     */
    #[Route('/pending-verification', name: 'admin_users_pending_verification', methods: ['GET'], priority: 10)]
    #[Template('admin/users/pending-verification.html.twig')]
    public function pendingVerification(): array
    {
        $professionals = $this->userRepository->findUnverifiedProfessionals();

        return [
            'professionals' => $professionals,
        ];
    }

    /**
     * Bulk activate users
     */
    #[Route('/bulk-activate', name: 'admin_users_bulk_activate', methods: ['POST'], priority: 10)]
    public function bulkActivate(Request $request): Response
    {
        $uuids = $request->request->get('uuids', []);
        
        if (empty($uuids)) {
            $this->addFlash('error', 'Aucun utilisateur sélectionné');
            return $this->redirectToRoute('admin_users_index');
        }

        if (!$this->isCsrfTokenValid('bulk-activate', $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('admin.csrf_invalid'));
            return $this->redirectToRoute('admin_users_index');
        }

        $activated = 0;
        foreach ($uuids as $uuid) {
            $user = $this->userRepository->findOneByUuid($uuid);
            if ($user && !$user->isIsActive()) {
                $user->setIsActive(true);
                $activated++;
            }
        }

        $this->entityManager->flush();
        $this->addFlash('success', "$activated utilisateur(s) activé(s)");

        return $this->redirectToRoute('admin_users_index');
    }

    /**
     * Bulk verify professionals
     */
    #[Route('/bulk-verify', name: 'admin_users_bulk_verify', methods: ['POST'], priority: 10)]
    public function bulkVerify(Request $request): Response
    {
        $uuids = $request->request->get('uuids', []);
        
        if (empty($uuids)) {
            $this->addFlash('error', 'Aucun professionnel sélectionné');
            return $this->redirectToRoute('admin_users_pending_verification');
        }

        if (!$this->isCsrfTokenValid('bulk-verify', $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('admin.csrf_invalid'));
            return $this->redirectToRoute('admin_users_pending_verification');
        }

        $verified = 0;
        foreach ($uuids as $uuid) {
            $user = $this->userRepository->findOneByUuid($uuid);
            
            if ($user) {
                // Only professionals can be verified
                if ($user instanceof Medecin || $user instanceof Coach || $user instanceof Nutritionist) {
                    if (!$user->isVerifiedByAdmin()) {
                        $user->setVerifiedByAdmin(true);
                        $user->setVerificationDate(new \DateTime());
                        $verified++;
                    }
                }
            }
        }

        $this->entityManager->flush();
        $this->addFlash('success', "$verified professionnel(s) vérifié(s)");

        return $this->redirectToRoute('admin_users_pending_verification');
    }

    /**
     * Bulk deactivate users
     */
    #[Route('/bulk-deactivate', name: 'admin_users_bulk_deactivate', methods: ['POST'], priority: 10)]
    public function bulkDeactivate(Request $request): Response
    {
        $uuids = $request->request->get('uuids', []);
        
        if (empty($uuids)) {
            $this->addFlash('error', 'Aucun utilisateur sélectionné');
            return $this->redirectToRoute('admin_users_index');
        }

        if (!$this->isCsrfTokenValid('bulk-deactivate', $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('admin.csrf_invalid'));
            return $this->redirectToRoute('admin_users_index');
        }

        $deactivated = 0;
        $currentUserEmail = $this->getUser()->getUserIdentifier();
        
        foreach ($uuids as $uuid) {
            $user = $this->userRepository->findOneByUuid($uuid);
            // Prevent deactivating yourself
            if ($user && $user->getUserIdentifier() !== $currentUserEmail && $user->isIsActive()) {
                $user->setIsActive(false);
                $deactivated++;
            }
        }

        $this->entityManager->flush();
        $this->addFlash('success', "$deactivated utilisateur(s) désactivé(s)");

        return $this->redirectToRoute('admin_users_index');
    }

    /**
     * Bulk delete users
     */
    #[Route('/bulk-delete', name: 'admin_users_bulk_delete', methods: ['POST'], priority: 10)]
    public function bulkDelete(Request $request): Response
    {
        $uuids = $request->request->get('uuids', []);
        
        if (empty($uuids)) {
            $this->addFlash('error', 'Aucun utilisateur sélectionné');
            return $this->redirectToRoute('admin_users_index');
        }

        if (!$this->isCsrfTokenValid('bulk-delete', $request->request->get('_token'))) {
            $this->addFlash('error', $this->translator->trans('admin.csrf_invalid'));
            return $this->redirectToRoute('admin_users_index');
        }

        $deleted = 0;
        $currentUserEmail = $this->getUser()->getUserIdentifier();
        
        foreach ($uuids as $uuid) {
            $user = $this->userRepository->findOneByUuid($uuid);
            
            if ($user) {
                // Prevent deleting yourself
                if ($user->getUserIdentifier() === $currentUserEmail) {
                    continue;
                }
                
                // Prevent deleting admins
                if ($user instanceof Administrator) {
                    continue;
                }
                
                $this->entityManager->remove($user);
                $deleted++;
            }
        }

        $this->entityManager->flush();
        $this->addFlash('success', "$deleted utilisateur(s) supprimé(s)");

        return $this->redirectToRoute('admin_users_index');
    }
}
