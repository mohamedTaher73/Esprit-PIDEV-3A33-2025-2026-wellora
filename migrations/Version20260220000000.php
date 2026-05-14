<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create professional_verification table for AI diploma verification';
    }

    public function up(Schema $schema): void
    {
        // Create professional_verification table
        $this->addSql('CREATE TABLE professional_verification (
            id INT AUTO_INCREMENT NOT NULL,
            professional_id INT NOT NULL,
            license_number VARCHAR(255) DEFAULT NULL,
            specialty VARCHAR(255) DEFAULT NULL,
            diploma_path VARCHAR(500) DEFAULT NULL,
            diploma_filename VARCHAR(255) DEFAULT NULL,
            extracted_data JSON DEFAULT NULL,
            confidence_score INT DEFAULT NULL,
            status VARCHAR(50) NOT NULL DEFAULT \'pending\',
            validation_details JSON DEFAULT NULL,
            forgery_indicators JSON DEFAULT NULL,
            rejection_reason LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            verified_at DATETIME DEFAULT NULL,
            reviewed_by VARCHAR(255) DEFAULT NULL,
            INDEX IDX_PROFESSIONAL (professional_id),
            INDEX IDX_STATUS (status),
            INDEX IDX_CREATED_AT (created_at),
            FOREIGN KEY (professional_id) REFERENCES users(id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE professional_verification');
    }
}
