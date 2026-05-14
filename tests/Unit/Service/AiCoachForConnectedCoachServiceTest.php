<?php

namespace Tests\Unit\Service;

use App\Service\AiCoachForConnectedCoachService;
use App\Entity\Goal;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use DateTime;

class AiCoachForConnectedCoachServiceTest extends TestCase
{
    private AiCoachForConnectedCoachService $aiCoachService;
    private MockObject|EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->aiCoachService = new AiCoachForConnectedCoachService($this->entityManager);
    }

    public function testGetAllGoalsForConnectedCoach(): void
    {
        $goal1 = new Goal();
        $goal2 = new Goal();
        
        $repository = $this->createMock(\Doctrine\ORM\EntityRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['coachId' => 'coach123'])
            ->willReturn([$goal1, $goal2]);
        
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->with(Goal::class)
            ->willReturn($repository);
        
        $result = $this->aiCoachService->getAllGoalsForConnectedCoach('coach123');
        
        $this->assertCount(2, $result);
    }

    public function testAnalyzeGoalWithUnauthorizedCoach(): void
    {
        $goal = new Goal();
        $goal->setCoachId('coach456');
        
        $result = $this->aiCoachService->analyzeGoal($goal, 'coach123');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Vous n\'êtes pas autorisé à analyser cet objectif', $result['error']);
    }

    public function testAnalyzeGoalReturnsAnalysis(): void
    {
        $goal = new Goal();
        $goal->setTitle('Weight Loss');
        $goal->setCoachId('coach123');
        $goal->setStartDate(new DateTime('-10 days'));
        $goal->setProgress(30);
        $goal->setDifficultyLevel('intermediate');
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $result = $this->aiCoachService->analyzeGoal($goal, 'coach123');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('goal', $result);
        $this->assertArrayHasKey('advice', $result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('riskLevel', $result);
    }

    public function testAnalyzeGoalCalculatesMetrics(): void
    {
        $goal = new Goal();
        $goal->setTitle('Fitness Goal');
        $goal->setCoachId('coach123');
        $goal->setStartDate(new DateTime('-5 days'));
        $goal->setProgress(20);
        $goal->setDifficultyLevel('beginner');
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $result = $this->aiCoachService->analyzeGoal($goal, 'coach123');
        
        $this->assertIsArray($result['metrics']);
        $this->assertArrayHasKey('daysSinceStart', $result['metrics']);
        $this->assertArrayHasKey('expectedProgress', $result['metrics']);
        $this->assertArrayHasKey('progressGap', $result['metrics']);
        $this->assertArrayHasKey('currentDifficulty', $result['metrics']);
    }
}
