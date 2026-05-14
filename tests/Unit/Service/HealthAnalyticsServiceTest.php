<?php

namespace Tests\Unit\Service\Health;

use App\Service\Health\HealthAnalyticsService;
use App\DTO\Health\HealthMetricDTO;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Repository\HealthentryRepository;
use App\Repository\HealthjournalRepository;

class HealthAnalyticsServiceTest extends TestCase
{
    private HealthAnalyticsService $healthService;
    private MockObject|HealthentryRepository $entryRepository;
    private MockObject|HealthjournalRepository $journalRepository;

    protected function setUp(): void
    {
        $this->entryRepository = $this->createMock(HealthentryRepository::class);
        $this->journalRepository = $this->createMock(HealthjournalRepository::class);
        
        $this->healthService = new HealthAnalyticsService(
            $this->entryRepository,
            $this->journalRepository,
            23.2
        );
    }

    public function testGetMetricsForJournalIdWithInvalidId(): void
    {
        $this->journalRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);
        
        $result = $this->healthService->getMetricsForJournalId(999);
        
        $this->assertInstanceOf(HealthMetricDTO::class, $result);
    }

    public function testConstructorWithDefaultBmi(): void
    {
        $reflection = new \ReflectionClass($this->healthService);
        $property = $reflection->getProperty('defaultBmi');
        $property->setAccessible(true);
        
        $defaultBmi = $property->getValue($this->healthService);
        
        $this->assertEquals(23.2, $defaultBmi);
    }

    public function testParseDateRangeFromJournalNameWithNull(): void
    {
        $reflection = new \ReflectionClass($this->healthService);
        $method = $reflection->getMethod('parseDateRangeFromJournalName');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->healthService, null);
        
        $this->assertNull($result);
    }

    public function testParseDateRangeFromJournalNameWithEmptyString(): void
    {
        $reflection = new \ReflectionClass($this->healthService);
        $method = $reflection->getMethod('parseDateRangeFromJournalName');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->healthService, '');
        
        $this->assertNull($result);
    }
}
