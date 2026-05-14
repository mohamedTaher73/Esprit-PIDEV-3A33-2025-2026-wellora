<?php

namespace Tests\Unit\Entity;

use App\Entity\Consultation;
use PHPUnit\Framework\TestCase;
use DateTime;

class ConsultationTest extends TestCase
{
    private Consultation $consultation;

    protected function setUp(): void
    {
        $this->consultation = new Consultation();
    }

    public function testReasonForVisitGetterAndSetter(): void
    {
        $reason = 'Annual checkup';
        
        $result = $this->consultation->setReasonForVisit($reason);
        
        $this->assertSame($reason, $this->consultation->getReasonForVisit());
        $this->assertInstanceOf(Consultation::class, $result);
    }

    public function testConsultationTypeGetterAndSetter(): void
    {
        $type = 'soap';
        
        $result = $this->consultation->setConsultationType($type);
        
        $this->assertSame($type, $this->consultation->getConsultationType());
        $this->assertInstanceOf(Consultation::class, $result);
    }

    public function testDurationGetterAndSetter(): void
    {
        $duration = 30;
        
        $result = $this->consultation->setDuration($duration);
        
        $this->assertSame($duration, $this->consultation->getDuration());
        $this->assertInstanceOf(Consultation::class, $result);
    }

    public function testFullObjectInitialization(): void
    {
        $type = 'prescription';
        $reason = 'Follow-up consultation';
        $symptoms = 'Patient reports mild headache';
        $duration = 45;
        $location = 'Clinic Room 101';
        
        $this->consultation
            ->setConsultationType($type)
            ->setReasonForVisit($reason)
            ->setSymptomsDescription($symptoms)
            ->setDuration($duration)
            ->setLocation($location);
        
        $this->assertEquals($type, $this->consultation->getConsultationType());
        $this->assertEquals($reason, $this->consultation->getReasonForVisit());
        $this->assertEquals($symptoms, $this->consultation->getSymptomsDescription());
        $this->assertEquals($duration, $this->consultation->getDuration());
        $this->assertEquals($location, $this->consultation->getLocation());
    }
}
