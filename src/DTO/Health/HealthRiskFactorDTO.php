<?php

declare(strict_types=1);

namespace App\DTO\Health;

use Symfony\Component\Serializer\Annotation\Groups;

final class HealthRiskFactorDTO
{
    /**
     * @param array<string> $triggeringConditions
     */
    public function __construct(
        #[Groups(['health_risk', 'health_export'])]
        public string $name,
        
        #[Groups(['health_risk', 'health_export'])]
        public string $description,
        
        #[Groups(['health_risk', 'health_export'])]
        public float $severity, // 0.0 to 1.0
        
        #[Groups(['health_risk', 'health_export'])]
        public array $triggeringConditions = [],
    ) {}
}
