<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513230141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ON DELETE CASCADE to consultation foreign keys in notificationrdv, ordonnance, and examens tables';
    }

    public function up(Schema $schema): void
    {
        // Drop the existing foreign keys without cascade
        $this->addSql('ALTER TABLE notificationrdv DROP FOREIGN KEY FK_9998473262FF6CDF');
        $this->addSql('ALTER TABLE ordonnance DROP FOREIGN KEY FK_924B326C62FF6CDF');
        $this->addSql('ALTER TABLE examens DROP FOREIGN KEY FK_B2E32DD762FF6CDF');
        
        // Recreate the foreign keys with ON DELETE CASCADE
        $this->addSql('ALTER TABLE notificationrdv ADD CONSTRAINT FK_9998473262FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ordonnance ADD CONSTRAINT FK_924B326C62FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE examens ADD CONSTRAINT FK_B2E32DD762FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Revert the foreign keys without cascade
        $this->addSql('ALTER TABLE notificationrdv DROP FOREIGN KEY FK_9998473262FF6CDF');
        $this->addSql('ALTER TABLE ordonnance DROP FOREIGN KEY FK_924B326C62FF6CDF');
        $this->addSql('ALTER TABLE examens DROP FOREIGN KEY FK_B2E32DD762FF6CDF');
        
        $this->addSql('ALTER TABLE notificationrdv ADD CONSTRAINT FK_9998473262FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id)');
        $this->addSql('ALTER TABLE ordonnance ADD CONSTRAINT FK_924B326C62FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id)');
        $this->addSql('ALTER TABLE examens ADD CONSTRAINT FK_B2E32DD762FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id)');
    }
}
