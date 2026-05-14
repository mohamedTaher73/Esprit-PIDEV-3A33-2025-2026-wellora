<?php

namespace Tests\Unit\Service;

use App\Service\DiplomaVerificationService;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class DiplomaVerificationServiceTest extends TestCase
{
    private DiplomaVerificationService $diplomaService;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|ParameterBagInterface $params;
    private MockObject|SluggerInterface $slugger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->slugger = $this->createMock(SluggerInterface::class);
        
        $this->params->method('get')->willReturn('/tmp');
        
        $this->diplomaService = new DiplomaVerificationService(
            $this->entityManager,
            $this->params,
            $this->slugger
        );
    }

    public function testExtractTextFromDiplomaWithNullPath(): void
    {
        $reflection = new \ReflectionClass($this->diplomaService);
        $method = $reflection->getMethod('extractTextFromDiploma');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->diplomaService, null);
        
        $this->assertEquals('', $result);
    }

    public function testExtractTextFromDiplomaWithEmptyPath(): void
    {
        $reflection = new \ReflectionClass($this->diplomaService);
        $method = $reflection->getMethod('extractTextFromDiploma');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->diplomaService, '');
        
        $this->assertEquals('', $result);
    }

    public function testParseExtractedTextWithEmptyText(): void
    {
        $reflection = new \ReflectionClass($this->diplomaService);
        $method = $reflection->getMethod('parseExtractedText');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->diplomaService, '');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('names', $result);
        $this->assertArrayHasKey('license_numbers', $result);
        $this->assertArrayHasKey('dates', $result);
        $this->assertEmpty($result['names']);
    }

    public function testParseExtractedTextWithValidData(): void
    {
        $reflection = new \ReflectionClass($this->diplomaService);
        $method = $reflection->getMethod('parseExtractedText');
        $method->setAccessible(true);
        
        $text = 'Nom: John Doe, License: 12345, Date: 2024-01-15';
        
        $result = $method->invoke($this->diplomaService, $text);
        
        $this->assertIsArray($result);
        $this->assertNotEmpty($result['names']);
    }
}
