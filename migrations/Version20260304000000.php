<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_id foreign key to nutrition_goals table and update column definitions';
    }

    public function up(Schema $schema): void
    {
        // First set orphaned user_ids to NULL (these are integer values that don't correspond to user UUIDs)
        $this->addSql('UPDATE nutrition_goals SET user_id = NULL WHERE user_id IS NOT NULL');

        // Change user_id column type from INT to VARCHAR(36)
        $this->addSql('ALTER TABLE nutrition_goals CHANGE user_id user_id VARCHAR(36) DEFAULT NULL');
        
        // Add foreign key for user_id in nutrition_goals
        $this->addSql('ALTER TABLE nutrition_goals ADD CONSTRAINT FK_AE09E63FA76ED395 FOREIGN KEY (user_id) REFERENCES users (uuid)');
        $this->addSql('CREATE INDEX IDX_AE09E63FA76ED395 ON nutrition_goals (user_id)');
        
        // Update various column definitions to match entity mappings
        $this->addSql('ALTER TABLE users CHANGE birthdate birthdate DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE phone phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE avatar_url avatar_url VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE address address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE reset_token reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE reset_token_expires_at reset_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE last_login_at last_login_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE locked_until locked_until DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE lot lot VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE token token VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE specialite specialite VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE license_number license_number VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE diploma_url diploma_url VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE verification_date verification_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE rating rating NUMERIC(5, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE email_verification_token email_verification_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE email_verification_expires_at email_verification_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE last_session_id last_session_id VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE google_id google_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE totp_secret totp_secret VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE backup_codes backup_codes JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE trusted_devices trusted_devices JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE plain_backup_codes plain_backup_codes JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE education education VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE certifications certifications VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE hospital_affiliations hospital_affiliations VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE awards awards VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE specializations specializations JSON DEFAULT NULL');
        
        $this->addSql('ALTER TABLE ai_conversations CHANGE metadata metadata JSON DEFAULT NULL, CHANGE intent intent VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE commentaire_publication CHANGE owner_patient_uuid owner_patient_uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE consultation CHANGE notes notes VARCHAR(500) DEFAULT NULL, CHANGE diagnoses diagnoses JSON DEFAULT NULL, CHANGE vitals vitals JSON DEFAULT NULL, CHANGE follow_up follow_up JSON DEFAULT NULL, CHANGE medecin_id medecin_id VARCHAR(36) DEFAULT NULL, CHANGE patient_id patient_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE conversation CHANGE last_message_at last_message_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE daily_plan CHANGE coach_id coach_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE doctor_availability CHANGE location location VARCHAR(50) DEFAULT NULL, CHANGE breaks breaks JSON DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE doctor_leaves CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE doctor_locations CHANGE address address VARCHAR(500) DEFAULT NULL, CHANGE phone phone VARCHAR(20) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE doctor_substitutions CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE examens CHANGE date_realisation date_realisation DATE DEFAULT NULL, CHANGE result_file result_file VARCHAR(255) DEFAULT NULL, CHANGE medecin_id medecin_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE exercise_plan CHANGE exercises exercises JSON NOT NULL, CHANGE focus focus VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE exercises CHANGE video_url video_url VARCHAR(255) DEFAULT NULL, CHANGE video_file_name video_file_name VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE user_id user_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE food_items CHANGE quantity quantity NUMERIC(5, 2) DEFAULT NULL, CHANGE unit unit VARCHAR(50) DEFAULT NULL, CHANGE protein protein NUMERIC(6, 1) DEFAULT NULL, CHANGE carbs carbs NUMERIC(6, 1) DEFAULT NULL, CHANGE fats fats NUMERIC(6, 1) DEFAULT NULL, CHANGE fiber fiber NUMERIC(5, 1) DEFAULT NULL, CHANGE sugar sugar NUMERIC(5, 1) DEFAULT NULL, CHANGE logged_at logged_at DATETIME DEFAULT NULL, CHANGE category category VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE food_logs CHANGE name name VARCHAR(255) DEFAULT NULL, CHANGE total_protein total_protein NUMERIC(6, 1) DEFAULT NULL, CHANGE total_carbs total_carbs NUMERIC(6, 1) DEFAULT NULL, CHANGE total_fats total_fats NUMERIC(6, 1) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE user_uuid user_uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE goal CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE end_date end_date DATE DEFAULT NULL, CHANGE relevant relevant VARCHAR(255) DEFAULT NULL, CHANGE difficulty_level difficulty_level VARCHAR(20) DEFAULT NULL, CHANGE target_audience target_audience VARCHAR(20) DEFAULT NULL, CHANGE target_value target_value DOUBLE PRECISION DEFAULT NULL, CHANGE current_value current_value DOUBLE PRECISION DEFAULT NULL, CHANGE unit unit VARCHAR(20) DEFAULT NULL, CHANGE goal_type goal_type VARCHAR(50) DEFAULT NULL, CHANGE frequency frequency VARCHAR(20) DEFAULT NULL, CHANGE preferred_time preferred_time TIME DEFAULT NULL, CHANGE preferred_days preferred_days JSON DEFAULT NULL, CHANGE weight_start weight_start DOUBLE PRECISION DEFAULT NULL, CHANGE weight_target weight_target DOUBLE PRECISION DEFAULT NULL, CHANGE last_ai_analysis last_ai_analysis DATETIME DEFAULT NULL, CHANGE ai_metrics ai_metrics JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE healthjournal CHANGE user_id user_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE meal_plans CHANGE protein protein NUMERIC(6, 1) DEFAULT NULL, CHANGE carbs carbs NUMERIC(6, 1) DEFAULT NULL, CHANGE fats fats NUMERIC(6, 1) DEFAULT NULL, CHANGE generated_at generated_at DATETIME DEFAULT NULL, CHANGE user_uuid user_uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE message CHANGE read_at read_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE notificationrdv CHANGE sent_at sent_at DATE DEFAULT NULL, CHANGE notifie_uuid notifie_uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE nutrition_consultations CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE patient_name patient_name VARCHAR(255) DEFAULT NULL, CHANGE nutritionist_name nutritionist_name VARCHAR(255) DEFAULT NULL, CHANGE price price DOUBLE PRECISION DEFAULT NULL, CHANGE patient_uuid patient_uuid VARCHAR(36) DEFAULT NULL, CHANGE nutritionist_uuid nutritionist_uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE nutrition_goals CHANGE name name VARCHAR(255) DEFAULT NULL, CHANGE goal_type goal_type VARCHAR(50) DEFAULT NULL, CHANGE weight_target weight_target NUMERIC(5, 2) DEFAULT NULL, CHANGE current_weight current_weight NUMERIC(5, 2) DEFAULT NULL, CHANGE start_weight start_weight NUMERIC(5, 2) DEFAULT NULL, CHANGE weekly_weight_change_target weekly_weight_change_target NUMERIC(5, 2) DEFAULT NULL, CHANGE expected_weight_change_per_week expected_weight_change_per_week NUMERIC(5, 2) DEFAULT NULL, CHANGE activity_level activity_level VARCHAR(50) DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE priority priority VARCHAR(50) DEFAULT NULL, CHANGE start_date start_date DATETIME DEFAULT NULL, CHANGE target_date target_date DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE nutrition_goal_achievements CHANGE icon icon VARCHAR(50) DEFAULT NULL, CHANGE tier tier VARCHAR(50) DEFAULT NULL, CHANGE unlocked_at unlocked_at DATETIME DEFAULT NULL, CHANGE metadata metadata JSON DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE nutrition_goal_adjustments CHANGE adjustment_type adjustment_type VARCHAR(50) DEFAULT NULL, CHANGE effective_from effective_from DATE DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE nutrition_goal_milestones CHANGE milestone_type milestone_type VARCHAR(50) DEFAULT NULL, CHANGE target_weight target_weight NUMERIC(5, 2) DEFAULT NULL, CHANGE target_value target_value NUMERIC(5, 2) DEFAULT NULL, CHANGE unit unit VARCHAR(20) DEFAULT NULL, CHANGE target_date target_date DATE DEFAULT NULL, CHANGE completed_at completed_at DATETIME DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE nutrition_goal_progress CHANGE weight weight NUMERIC(5, 2) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE ordonnance CHANGE instructions instructions VARCHAR(500) DEFAULT NULL, CHANGE frequency frequency VARCHAR(50) DEFAULT NULL, CHANGE diagnosis_code diagnosis_code VARCHAR(20) DEFAULT NULL, CHANGE medecin_id medecin_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE parcours_de_sante CHANGE latitude_parcours latitude_parcours DOUBLE PRECISION DEFAULT NULL, CHANGE longitude_parcours longitude_parcours DOUBLE PRECISION DEFAULT NULL, CHANGE image_parcours image_parcours VARCHAR(255) DEFAULT NULL, CHANGE owner_patient_uuid owner_patient_uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE professional_verifications CHANGE professional_email professional_email VARCHAR(255) DEFAULT NULL, CHANGE license_number license_number VARCHAR(255) DEFAULT NULL, CHANGE specialty specialty VARCHAR(255) DEFAULT NULL, CHANGE diploma_path diploma_path VARCHAR(500) DEFAULT NULL, CHANGE diploma_filename diploma_filename VARCHAR(255) DEFAULT NULL, CHANGE extracted_data extracted_data JSON DEFAULT NULL, CHANGE validation_details validation_details JSON DEFAULT NULL, CHANGE forgery_indicators forgery_indicators JSON DEFAULT NULL, CHANGE verified_at verified_at DATETIME DEFAULT NULL, CHANGE reviewed_by reviewed_by VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE publication_parcours CHANGE owner_patient_uuid owner_patient_uuid VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE symptom CHANGE zone zone VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE water_intakes CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE user_uuid user_uuid VARCHAR(36) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE nutrition_goals DROP FOREIGN KEY FK_AE09E63FA76ED395');
        $this->addSql('DROP INDEX IDX_AE09E63FA76ED395 ON nutrition_goals');
    }
}
