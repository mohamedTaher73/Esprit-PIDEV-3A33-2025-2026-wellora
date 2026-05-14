<?php

declare(strict_types=1);

namespace App\DTO\Health;

use Symfony\Component\Serializer\Annotation\Groups;

final class HealthTrendDTO
{
    /**
     * @param array<string, float> $metricEvolutions
     */
    public function __construct(
        #[Groups(['health_trends', 'health_export'])]
        public ?HealthScoreDTO $currentScore = null,
        
        #[Groups(['health_trends', 'health_export'])]
        public ?HealthScoreDTO $previousScore = null,
        
        #[Groups(['health_trends', 'health_export'])]
        public float $globalEvolutionPercentage = 0.0,
        
        #[Groups(['health_trends', 'health_export'])]
        public HealthTrendDirection $globalDirection = HealthTrendDirection::UNKNOWN,
        
        #[Groups(['health_trends', 'health_export'])]
        public array $metricEvolutions = [],
        
        #[Groups(['health_trends', 'health_export'])]
        public bool $hasPreviousData = false,
        
        #[Groups(['health_trends', 'health_export'])]
        public ?\DateTimeInterface $previousPeriodStart = null,
        
        #[Groups(['health_trends', 'health_export'])]
        public ?\DateTimeInterface $previousPeriodEnd = null,
    ) {}
    
    public function getMetricEvolution(string $metricName): ?float
    {
        return $this->metricEvolutions[$metricName] ?? null;
    }
}
