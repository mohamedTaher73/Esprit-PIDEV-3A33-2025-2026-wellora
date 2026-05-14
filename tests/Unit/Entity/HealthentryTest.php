<?php

namespace Tests\Unit\Entity;

use App\Entity\Healthentry;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;

class HealthentryTest extends TestCase
{
    private Healthentry $healthEntry;

    protected function setUp(): void
    {
        $this->healthEntry = new Healthentry();
    }

    public function testPoidsGetterAndSetter(): void
    {
        $poids = 75.5;
        
        $result = $this->healthEntry->setPoids($poids);
        
        $this->assertSame($poids, $this->healthEntry->getPoids());
        $this->assertInstanceOf(Healthentry::class, $result);
    }

    public function testGlycemieGetterAndSetter(): void
    {
        $glycemie = 1.2;
        
        $result = $this->healthEntry->setGlycemie($glycemie);
        
        $this->assertSame($glycemie, $this->healthEntry->getGlycemie());
        $this->assertInstanceOf(Healthentry::class, $result);
    }

    public function testSommeilGetterAndSetter(): void
    {
        $sommeil = 7;
        
        $result = $this->healthEntry->setSommeil($sommeil);
        
        $this->assertSame($sommeil, $this->healthEntry->getSommeil());
        $this->assertInstanceOf(Healthentry::class, $result);
    }

    public function testFullObjectInitialization(): void
    {
        $date = new DateTime('2024-01-15');
        $poids = 70.0;
        $glycemie = 1.0;
        $tension = '120/80';
        $sommeil = 8;
        
        $this->healthEntry
            ->setDate($date)
            ->setPoids($poids)
            ->setGlycemie($glycemie)
            ->setTension($tension)
            ->setSommeil($sommeil);
        
        $this->assertEquals($date, $this->healthEntry->getDate());
        $this->assertEquals($poids, $this->healthEntry->getPoids());
        $this->assertEquals($glycemie, $this->healthEntry->getGlycemie());
        $this->assertEquals($tension, $this->healthEntry->getTension());
        $this->assertEquals($sommeil, $this->healthEntry->getSommeil());
    }
}
