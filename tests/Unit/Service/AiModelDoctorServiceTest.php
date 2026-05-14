<?php

namespace Tests\Unit\Service;

use App\Service\AiModelDoctorService;
use App\Repository\ConsultationRepository;
use App\Repository\MedecinRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class AiModelDoctorServiceTest extends TestCase
{
    private AiModelDoctorService $aiModelDoctorService;
    private MockObject|HttpClientInterface $httpClient;
    private MockObject|LoggerInterface $logger;
    private MockObject|ConsultationRepository $consultationRepository;
    private MockObject|MedecinRepository $medecinRepository;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->consultationRepository = $this->createMock(ConsultationRepository::class);
        $this->medecinRepository = $this->createMock(MedecinRepository::class);
        
        $this->aiModelDoctorService = new AiModelDoctorService(
            $this->httpClient,
            $this->logger,
            $this->consultationRepository,
            $this->medecinRepository,
            'http://localhost:5000',
            false // disabled by default
        );
    }

    public function testIsAvailableWhenDisabled(): void
    {
        $result = $this->aiModelDoctorService->isAvailable();
        
        $this->assertFalse($result);
    }

    public function testPredictDoctorActivityWhenDisabled(): void
    {
        $result = $this->aiModelDoctorService->predictDoctorActivity('doctor123');
        
        $this->assertNull($result);
    }

    public function testGetDoctorProfitPredictionsWithNoData(): void
    {
        $this->consultationRepository->expects($this->once())
            ->method('getDoctorRevenueSeries')
            ->willReturn([]);
        
        $result = $this->aiModelDoctorService->getDoctorProfitPredictions('doctor123');
        
        $this->assertNull($result);
    }

    public function testGetDoctorProfitAlertsWithNoProfitData(): void
    {
        $this->consultationRepository->expects($this->once())
            ->method('getDoctorRevenueSeries')
            ->willReturn([]);
        
        $result = $this->aiModelDoctorService->getDoctorProfitAlerts('doctor123');
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
