<?php

namespace App\Repository;

use App\Entity\ProfessionalVerification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProfessionalVerification>
 */
class ProfessionalVerificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfessionalVerification::class);
    }

    /**
     * Find all pending verifications
     */
    public function findPendingVerifications(): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.status = :status')
            ->setParameter('status', ProfessionalVerification::STATUS_PENDING)
            ->orderBy('v.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find verifications needing manual review
     */
    public function findManualReviewVerifications(): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.status = :status')
            ->setParameter('status', ProfessionalVerification::STATUS_MANUAL_REVIEW)
            ->orderBy('v.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find verifications by professional UUID
     */
    public function findByProfessionalUuid(string $professionalUuid): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.professionalUuid = :professionalUuid')
            ->setParameter('professionalUuid', $professionalUuid)
            ->orderBy('v.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find verification by professional UUID (single result)
     */
    public function findOneByProfessionalUuid(string $professionalUuid): ?ProfessionalVerification
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.professionalUuid = :professionalUuid')
            ->setParameter('professionalUuid', $professionalUuid)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find verification by user
     * @deprecated Use findOneByProfessionalUuid instead
     */
    public function findByUser($user): ?ProfessionalVerification
    {
        if (method_exists($user, 'getUuid')) {
            return $this->findOneByProfessionalUuid($user->getUuid()->toRfc4122());
        }
        return null;
    }

    /**
     * Find recent verifications for admin dashboard
     */
    public function findRecentVerifications(int $limit = 10): array
    {
        return $this->createQueryBuilder('v')
            ->orderBy('v.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count verifications by status
     */
    public function countByStatus(string $status): int
    {
        return $this->createQueryBuilder('v')
            ->select('COUNT(v)')
            ->andWhere('v.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get verification statistics
     */
    public function getStatistics(): array
    {
        return [
            'pending' => $this->countByStatus(ProfessionalVerification::STATUS_PENDING),
            'processing' => $this->countByStatus(ProfessionalVerification::STATUS_PROCESSING),
            'verified' => $this->countByStatus(ProfessionalVerification::STATUS_VERIFIED),
            'rejected' => $this->countByStatus(ProfessionalVerification::STATUS_REJECTED),
            'manual_review' => $this->countByStatus(ProfessionalVerification::STATUS_MANUAL_REVIEW),
        ];
    }
}
