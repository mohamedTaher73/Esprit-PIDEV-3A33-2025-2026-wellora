<?php

namespace App\Controller;

use App\Entity\Healthentry;
use App\Entity\Healthjournal;
use App\Entity\Symptom;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestDataController extends AbstractController
{
    #[Route('/test/data', name: 'app_test_data')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // 1️⃣ Create a Healthjournal
        $journal = new Healthjournal();
        $journal->setDatedebut(new \DateTime('2026-01-01'));
        $journal->setDatefin(new \DateTime('2026-12-31'));
        $entityManager->persist($journal);

        // 2️⃣ Create a Healthentry linked to the journal
        $healthentry = new Healthentry();
        $healthentry->setJournal($journal); // ✅ must set journal
        $healthentry->setDate(new \DateTime());
        $healthentry->setDateEntry(new \DateTime());
        $healthentry->setPoids(70.5);
        $healthentry->setGlycemie(1.0);
        $healthentry->setTension('12.0');
        $healthentry->setSommeil(8);
        $entityManager->persist($healthentry);

        // 3️⃣ Create a Symptom linked to the entry
        $symptom = new Symptom();
        $symptom->setEntry($healthentry); // ✅ must set entry
        $symptom->setType('Headache');
        $symptom->setTypeSymptom('Headache');
        $symptom->setIntensite(5);
        $symptom->setDateSymptom(new \DateTime());
        $symptom->setDateObservation(new \DateTime());
        $entityManager->persist($symptom);

        // 4️⃣ Flush all changes
        $entityManager->flush();

        return new Response('Test data created successfully!');
    }
}
