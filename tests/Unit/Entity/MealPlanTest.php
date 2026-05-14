<?php

namespace Tests\Unit\Entity;

use App\Entity\MealPlan;
use PHPUnit\Framework\TestCase;
use DateTime;

class MealPlanTest extends TestCase
{
    private MealPlan $mealPlan;

    protected function setUp(): void
    {
        $this->mealPlan = new MealPlan();
    }

    public function testNameGetterAndSetter(): void
    {
        $name = 'Healthy Breakfast';
        
        $result = $this->mealPlan->setName($name);
        
        $this->assertSame($name, $this->mealPlan->getName());
        $this->assertInstanceOf(MealPlan::class, $result);
    }

    public function testMealTypeGetterAndSetter(): void
    {
        $mealType = 'breakfast';
        
        $result = $this->mealPlan->setMealType($mealType);
        
        $this->assertSame($mealType, $this->mealPlan->getMealType());
        $this->assertInstanceOf(MealPlan::class, $result);
    }

    public function testCaloriesGetterAndSetter(): void
    {
        $calories = 500;
        
        $result = $this->mealPlan->setCalories($calories);
        
        $this->assertSame($calories, $this->mealPlan->getCalories());
        $this->assertInstanceOf(MealPlan::class, $result);
    }

    public function testFullObjectInitialization(): void
    {
        $name = 'Grilled Chicken Salad';
        $mealType = 'lunch';
        $calories = 350;
        $protein = '30.5';
        $carbs = '25.0';
        $fats = '10.5';
        
        $this->mealPlan
            ->setName($name)
            ->setMealType($mealType)
            ->setCalories($calories)
            ->setProtein($protein)
            ->setCarbs($carbs)
            ->setFats($fats);
        
        $this->assertEquals($name, $this->mealPlan->getName());
        $this->assertEquals($mealType, $this->mealPlan->getMealType());
        $this->assertEquals($calories, $this->mealPlan->getCalories());
        $this->assertEquals($protein, $this->mealPlan->getProtein());
        $this->assertEquals($carbs, $this->mealPlan->getCarbs());
        $this->assertEquals($fats, $this->mealPlan->getFats());
    }
}
