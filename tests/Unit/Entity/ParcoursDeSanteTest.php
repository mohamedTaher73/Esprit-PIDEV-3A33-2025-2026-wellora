<?php

namespace Tests\Unit\Entity;

use App\Entity\ParcoursDeSante;
use App\Entity\PublicationParcours;
use App\Entity\Patient;
use PHPUnit\Framework\TestCase;
use Doctrine\Common\Collections\ArrayCollection;
use DateTime;

class ParcoursDeSanteTest extends TestCase
{
    private ParcoursDeSante $parcours;

    protected function setUp(): void
    {
        $this->parcours = new ParcoursDeSante();
    }

    public function testInitialPublicationParcoursCollectionIsEmpty(): void
    {
        $this->assertInstanceOf(ArrayCollection::class, $this->parcours->getPublicationParcours());
        $this->assertCount(0, $this->parcours->getPublicationParcours());
    }

    public function testNomParcoursGetterAndSetter(): void
    {
        $nomParcours = 'Forest Trail';
        
        $result = $this->parcours->setNomParcours($nomParcours);
        
        $this->assertSame($nomParcours, $this->parcours->getNomParcours());
        $this->assertInstanceOf(ParcoursDeSante::class, $result);
    }

    public function testAddPublicationParcours(): void
    {
        $publication = $this->createMock(PublicationParcours::class);
        
        $result = $this->parcours->addPublicationParcour($publication);
        
        $this->assertCount(1, $this->parcours->getPublicationParcours());
        $this->assertTrue($this->parcours->getPublicationParcours()->contains($publication));
        $this->assertInstanceOf(ParcoursDeSante::class, $result);
    }

    public function testFullObjectInitialization(): void
    {
        $nomParcours = 'Mountain Trail';
        $localisation = 'Atlas Mountains';
        $distance = 10.5;
        $latitude = 35.5;
        $longitude = -5.5;
        $dateCreation = new DateTime('2024-01-01');
        $imageParcours = '/uploads/mountain.jpg';
        
        $this->parcours
            ->setNomParcours($nomParcours)
            ->setLocalisationParcours($localisation)
            ->setDistanceParcours($distance)
            ->setLatitudeParcours($latitude)
            ->setLongitudeParcours($longitude)
            ->setDateCreation($dateCreation)
            ->setImageParcours($imageParcours);
        
        $this->assertEquals($nomParcours, $this->parcours->getNomParcours());
        $this->assertEquals($localisation, $this->parcours->getLocalisationParcours());
        $this->assertEquals($distance, $this->parcours->getDistanceParcours());
        $this->assertEquals($latitude, $this->parcours->getLatitudeParcours());
        $this->assertEquals($longitude, $this->parcours->getLongitudeParcours());
        $this->assertEquals($dateCreation, $this->parcours->getDateCreation());
        $this->assertEquals($imageParcours, $this->parcours->getImageParcours());
    }
}
