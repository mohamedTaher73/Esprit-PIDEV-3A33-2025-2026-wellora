<?php

namespace App\Command;

use App\Entity\ProfessionalVerification;
use App\Service\DiplomaVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:reprocess-verifications',
    description: 'Reprocess professional verification requests to extract OCR data',
)]
class ReprocessVerificationsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DiplomaVerificationService $diplomaVerificationService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Reprocessing Professional Verifications');

        // Get all pending or manual review verifications that have a diploma
        $verifications = $this->entityManager
            ->getRepository(ProfessionalVerification::class)
            ->createQueryBuilder('v')
            ->where('v.diplomaPath IS NOT NULL')
            ->andWhere('v.diplomaPath != \'\'')
            ->getQuery()
            ->getResult();

        if (empty($verifications)) {
            $io->warning('No verifications with diploma found.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d verifications to reprocess.', count($verifications)));

        $successCount = 0;
        $errorCount = 0;

        foreach ($verifications as $verification) {
            try {
                $io->section(sprintf(
                    'Processing verification #%d for %s',
                    $verification->getId(),
                    $verification->getProfessionalUuid()
                ));

                // Reprocess the verification
                $this->diplomaVerificationService->processVerification($verification);
                $this->entityManager->flush();

                $io->success(sprintf(
                    'Verification #%d processed. Score: %d, Status: %s',
                    $verification->getId(),
                    $verification->getConfidenceScore() ?? 0,
                    $verification->getStatus()
                ));

                $successCount++;
            } catch (\Exception $e) {
                $io->error(sprintf(
                    'Error processing verification #%d: %s',
                    $verification->getId(),
                    $e->getMessage()
                ));
                $errorCount++;
            }
        }

        $io->title('Reprocessing Complete');
        $io->info(sprintf('Success: %d, Errors: %d', $successCount, $errorCount));

        return Command::SUCCESS;
    }
}
