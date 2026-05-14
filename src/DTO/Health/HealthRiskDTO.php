<?php

declare(strict_types=1);

namespace App\DTO\Health;

use Symfony\Component\Serializer\Annotation\Groups;

final class HealthRiskDTO
{
    /**
     * @param array<HealthRiskFactorDTO> $riskFactors
     * @param array<string> $recommendations
     */
    public function __construct(
        #[Groups(['health_risk', 'health_export'])]
        public HealthRiskTier $tier = HealthRiskTier::UNKNOWN,
        
        #[Groups(['health_risk', 'health_export'])]
        public array $riskFactors = [],
        
        #[Groups(['health_risk', 'health_export'])]
        public float $overallRiskScore = 0.0,
        
        #[Groups(['health_risk', 'health_export'])]
        public string $summary = '',
        
        #[Groups(['health_risk', 'health_export'])]
        public array $recommendations = [],
        
        #[Groups(['health_risk', 'health_export'])]
        public bool $requiresImmediateAttention = false,
    ) {}
    
    /**
     * Check if there are any risk factors detected
     */
    public function hasRiskFactors(): bool
    {
        return !empty($this->riskFactors);
    }
}
