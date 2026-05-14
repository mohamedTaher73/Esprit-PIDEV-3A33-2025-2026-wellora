<?php

namespace Tests\Unit\Entity;

use App\Entity\Exercises;
use PHPUnit\Framework\TestCase;

class ExercisesTest extends TestCase
{
    private Exercises $exercises;

    protected function setUp(): void
    {
        $this->exercises = new Exercises();
    }

    public function testNameGetterAndSetter(): void
    {
        $name = 'Push-ups';
        
        $result = $this->exercises->setName($name);
        
        $this->assertSame($name, $this->exercises->getName());
        $this->assertInstanceOf(Exercises::class, $result);
    }

    public function testCategoryGetterAndSetter(): void
    {
        $category = 'Strength';
        
        $result = $this->exercises->setCategory($category);
        
        $this->assertSame($category, $this->exercises->getCategory());
        $this->assertInstanceOf(Exercises::class, $result);
    }

    public function testDifficultyLevelGetterAndSetter(): void
    {
        $difficulty = 'Beginner';
        
        $result = $this->exercises->setDifficultyLevel($difficulty);
        
        $this->assertSame($difficulty, $this->exercises->getDifficultyLevel());
        $this->assertInstanceOf(Exercises::class, $result);
    }

    public function testFullObjectInitialization(): void
    {
        $name = 'Squats';
        $description = 'Lower body exercise';
        $category = 'Strength';
        $difficulty = 'Intermediate';
        $duration = 30;
        $calories = 200;
        
        $this->exercises
            ->setName($name)
            ->setDescription($description)
            ->setCategory($category)
            ->setDifficultyLevel($difficulty)
            ->setDuration($duration)
            ->setCalories($calories);
        
        $this->assertEquals($name, $this->exercises->getName());
        $this->assertEquals($description, $this->exercises->getDescription());
        $this->assertEquals($category, $this->exercises->getCategory());
        $this->assertEquals($difficulty, $this->exercises->getDifficultyLevel());
        $this->assertEquals($duration, $this->exercises->getDuration());
        $this->assertEquals($calories, $this->exercises->getCalories());
    }
}
