
-- FLEXJOB latest baseline schema
-- Import only into a new, empty database. It contains required reference data
-- (job categories, interests, broad skills and promotion packages), but no
-- users, employer profiles, jobs, applications, uploaded files or email log.
-- The migration history below records this snapshot as version 0008.
CREATE DATABASE IF NOT EXISTS db_flexjob CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_flexjob;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `applications` (
  `application_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `worker_user_id` int(10) unsigned NOT NULL,
  `resume_file_path` varchar(255) DEFAULT NULL,
  `cover_note` text DEFAULT NULL,
  `application_status` enum('submitted','eligible','interview_passed','completed','not_selected','withdrawn') NOT NULL DEFAULT 'submitted',
  `withdrawn_at` datetime DEFAULT NULL,
  `rating_by_worker` tinyint(3) unsigned DEFAULT NULL,
  `rated_by_worker_at` timestamp NULL DEFAULT NULL,
  `rating_by_employer` tinyint(3) unsigned DEFAULT NULL,
  `rated_by_employer_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`application_id`),
  UNIQUE KEY `uq_application_job_worker` (`job_id`,`worker_user_id`),
  KEY `idx_application_worker` (`worker_user_id`,`application_status`),
  CONSTRAINT `fk_application_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_application_worker` FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_tokens` (
  `auth_token_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` char(64) NOT NULL,
  `token_type` enum('email_verification','password_reset') NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`auth_token_id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_auth_token_lookup` (`user_id`,`token_type`,`used_at`),
  CONSTRAINT `fk_auth_token_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `to_email` varchar(190) NOT NULL,
  `to_name` varchar(190) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `html_body` longtext DEFAULT NULL,
  `reply_to_email` varchar(190) DEFAULT NULL,
  `reply_to_name` varchar(190) DEFAULT NULL,
  `status` enum('queued','processing','sent','failed') NOT NULL DEFAULT 'queued',
  `error_msg` text DEFAULT NULL,
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `available_at` datetime NOT NULL DEFAULT current_timestamp(),
  `locked_at` datetime DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_to` (`to_email`),
  KEY `idx_email_log_delivery_queue` (`status`,`available_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employer_documents` (
  `employer_document_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employer_user_id` int(10) unsigned NOT NULL,
  `document_file_path` varchar(255) NOT NULL,
  `document_status` enum('pending','approved','rejected','resubmit') NOT NULL DEFAULT 'pending',
  `review_note` text DEFAULT NULL,
  `reviewed_by_user_id` int(10) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`employer_document_id`),
  KEY `fk_employer_document_reviewer` (`reviewed_by_user_id`),
  KEY `idx_employer_document_status` (`employer_user_id`,`document_status`),
  CONSTRAINT `fk_employer_document_employer` FOREIGN KEY (`employer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_employer_document_reviewer` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employer_profiles` (
  `employer_profile_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `company_name` varchar(180) NOT NULL,
  `company_description` text DEFAULT NULL,
  `company_address` text DEFAULT NULL,
  `company_logo_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`employer_profile_id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `fk_employer_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_categories` (
  `job_category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_slug` varchar(100) NOT NULL,
  PRIMARY KEY (`job_category_id`),
  UNIQUE KEY `category_slug` (`category_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_images` (
  `job_image_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `image_file_path` varchar(255) NOT NULL,
  `display_order` smallint(5) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`job_image_id`),
  KEY `idx_job_image_order` (`job_id`,`display_order`),
  CONSTRAINT `fk_job_image_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_invitations` (
  `job_invitation_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `worker_user_id` int(10) unsigned NOT NULL,
  `invitation_message` text DEFAULT NULL,
  `invitation_status` enum('sent','viewed','accepted','declined') NOT NULL DEFAULT 'sent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`job_invitation_id`),
  UNIQUE KEY `uq_job_invitation_worker` (`job_id`,`worker_user_id`),
  KEY `idx_worker_invitation_inbox` (`worker_user_id`,`invitation_status`,`created_at`),
  CONSTRAINT `fk_job_invitation_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_job_invitation_worker` FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_promotions` (
  `promotion_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `job_id` int(10) unsigned NOT NULL,
  `employer_user_id` int(10) unsigned NOT NULL,
  `package_id` int(10) unsigned NOT NULL,
  `package_name_snapshot` varchar(120) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `duration_days` smallint(5) unsigned NOT NULL,
  `promotion_status` enum('pending_payment','pending_verification','active','rejected','expired','cancelled') NOT NULL DEFAULT 'pending_payment',
  `payment_slip_path` varchar(255) DEFAULT NULL,
  `payment_reference` varchar(120) DEFAULT NULL,
  `payment_submitted_at` datetime DEFAULT NULL,
  `reviewed_by_user_id` int(10) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `review_note` text DEFAULT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`promotion_id`),
  KEY `fk_job_promotion_package` (`package_id`),
  KEY `fk_job_promotion_reviewer` (`reviewed_by_user_id`),
  KEY `idx_promotion_job_status` (`job_id`,`promotion_status`,`ends_at`),
  KEY `idx_promotion_review_queue` (`promotion_status`,`payment_submitted_at`),
  KEY `idx_promotion_employer` (`employer_user_id`,`created_at`),
  CONSTRAINT `fk_job_promotion_employer` FOREIGN KEY (`employer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_job_promotion_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_job_promotion_package` FOREIGN KEY (`package_id`) REFERENCES `promotion_packages` (`package_id`),
  CONSTRAINT `fk_job_promotion_reviewer` FOREIGN KEY (`reviewed_by_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_skills` (
  `job_id` int(10) unsigned NOT NULL,
  `skill_id` int(10) unsigned NOT NULL,
  `importance` enum('required','preferred') NOT NULL DEFAULT 'required',
  PRIMARY KEY (`job_id`,`skill_id`),
  KEY `idx_job_skill_lookup` (`skill_id`,`importance`,`job_id`),
  CONSTRAINT `fk_job_skill_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_job_skill_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_worker_matches` (
  `job_id` int(10) unsigned NOT NULL,
  `worker_user_id` int(10) unsigned NOT NULL,
  `match_score` tinyint(3) unsigned DEFAULT NULL,
  `data_strength` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `match_reasons_json` text NOT NULL,
  `missing_required_json` text NOT NULL,
  `required_skills_json` text NOT NULL,
  `preferred_skills_json` text NOT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`job_id`,`worker_user_id`),
  KEY `idx_job_worker_matches_job_score` (`job_id`,`match_score`),
  KEY `idx_job_worker_matches_worker_score` (`worker_user_id`,`match_score`),
  CONSTRAINT `fk_job_worker_matches_job` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`job_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_job_worker_matches_worker` FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `job_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employer_user_id` int(10) unsigned NOT NULL,
  `job_category_id` int(10) unsigned NOT NULL,
  `work_interest_id` int(10) unsigned DEFAULT NULL,
  `job_title` varchar(180) NOT NULL,
  `job_description` text NOT NULL,
  `work_location` varchar(180) NOT NULL,
  `work_province` varchar(100) DEFAULT NULL,
  `work_schedule` varchar(180) DEFAULT NULL,
  `work_start_date` date DEFAULT NULL,
  `work_end_date` date DEFAULT NULL,
  `work_start_time` time DEFAULT NULL,
  `work_end_time` time DEFAULT NULL,
  `work_mode` enum('onsite','remote','hybrid') NOT NULL DEFAULT 'onsite',
  `application_deadline` date DEFAULT NULL,
  `pay_amount` decimal(10,2) NOT NULL,
  `pay_unit` enum('hour','day','project') NOT NULL DEFAULT 'day',
  `open_positions` smallint(5) unsigned NOT NULL DEFAULT 1,
  `job_status` enum('published','hidden','closed') NOT NULL DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`job_id`),
  KEY `fk_job_employer` (`employer_user_id`),
  KEY `fk_job_category` (`job_category_id`),
  KEY `idx_job_listing` (`job_status`,`job_category_id`,`created_at`),
  KEY `fk_job_work_interest` (`work_interest_id`),
  CONSTRAINT `fk_job_category` FOREIGN KEY (`job_category_id`) REFERENCES `job_categories` (`job_category_id`),
  CONSTRAINT `fk_job_employer` FOREIGN KEY (`employer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_job_work_interest` FOREIGN KEY (`work_interest_id`) REFERENCES `work_interests` (`work_interest_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `notification_title` varchar(180) NOT NULL,
  `notification_message` text NOT NULL,
  `notification_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `idx_notification_inbox` (`user_id`,`is_read`,`created_at`),
  CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `promotion_packages` (
  `package_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `package_code` varchar(50) NOT NULL,
  `package_name` varchar(120) NOT NULL,
  `package_description` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `duration_days` smallint(5) unsigned NOT NULL,
  `display_priority` smallint(5) unsigned NOT NULL DEFAULT 10,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`package_id`),
  UNIQUE KEY `package_code` (`package_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schema_migrations` (
  `migration` varchar(255) NOT NULL,
  `checksum` char(64) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO schema_migrations (migration, checksum) VALUES
  ('0001_initial_schema.sql', '2f51249b791e63cc5d30974b1fb32d8379e24a3e02d41b166d19a87e83676919'),
  ('0002_job_promotions.sql', 'e7bb7a63fc9204274eff3c4e0c1f2693adf05f4993fc6653641c6ba51e51523a'),
  ('0003_broad_skill_catalog.sql', '7e6158400208076a7ec309ae81d3225492b9e1bd3d575fb7f69765814d6e84bc'),
  ('0004_job_worker_matches.sql', '4668f330933b348fdf31787fb811539a5ffbed42375102b1c2c907af06b89457'),
  ('0005_worker_survey_onboarding.sql', '3ab0e85cadfcf8d02a61743516df58f1ec7cf30b57d5ef858d8c4989447cb6b2'),
  ('0006_refresh_sample_jobs_buriram.sql', '98007d682875f4f08c00e082564ed18741116234466cfc7a94987a96d6dd61cc'),
  ('0007_add_structured_work_schedule.sql', 'dbb057a03630acc063a3a53fc9639a02d96e4a3c6b3b9d756ed61016083bee86'),
  ('0008_email_delivery_queue.sql', 'efde1d0811fdfcab714bf7b5a1c1a600363611194263a33b254ae35ac737f9fc');
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `skill_categories` (
  `skill_category_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_slug` varchar(100) NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`skill_category_id`),
  UNIQUE KEY `category_slug` (`category_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `skill_consolidation_map` (
  `legacy_skill_id` int(10) unsigned NOT NULL,
  `broad_skill_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`legacy_skill_id`),
  KEY `idx_skill_map_broad` (`broad_skill_id`),
  CONSTRAINT `fk_skill_map_broad` FOREIGN KEY (`broad_skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_skill_map_legacy` FOREIGN KEY (`legacy_skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `skills` (
  `skill_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `skill_category_id` int(10) unsigned DEFAULT NULL,
  `skill_name` varchar(100) NOT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `retired_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`skill_id`),
  UNIQUE KEY `uq_skill_name` (`skill_name`),
  KEY `idx_skill_catalog` (`skill_category_id`,`is_active`),
  CONSTRAINT `fk_skill_category` FOREIGN KEY (`skill_category_id`) REFERENCES `skill_categories` (`skill_category_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','employer','worker') NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `account_status` enum('active','suspended','pending') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `email_verified_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `work_interests` (
  `work_interest_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `interest_slug` varchar(100) NOT NULL,
  `interest_name` varchar(180) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`work_interest_id`),
  UNIQUE KEY `uq_work_interest_slug` (`interest_slug`),
  KEY `idx_work_interest_active` (`is_active`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker_job_preferences` (
  `worker_user_id` int(10) unsigned NOT NULL,
  `job_category_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`worker_user_id`,`job_category_id`),
  KEY `idx_worker_preference_category` (`job_category_id`,`worker_user_id`),
  CONSTRAINT `fk_worker_preference_category` FOREIGN KEY (`job_category_id`) REFERENCES `job_categories` (`job_category_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_worker_preference_worker` FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker_profiles` (
  `worker_profile_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `professional_headline` varchar(180) DEFAULT NULL,
  `biography` text DEFAULT NULL,
  `profile_image_path` varchar(255) DEFAULT NULL,
  `resume_file_path` varchar(255) DEFAULT NULL,
  `portfolio_file_path` varchar(255) DEFAULT NULL,
  `portfolio_url` varchar(500) DEFAULT NULL,
  `profile_visibility` enum('application_only','searchable') NOT NULL DEFAULT 'application_only',
  `work_province` varchar(100) DEFAULT NULL,
  `preferred_work_mode` enum('any','onsite','remote','hybrid') NOT NULL DEFAULT 'any',
  `available_from` date DEFAULT NULL,
  `matching_survey_required_at` datetime DEFAULT NULL,
  `matching_survey_completed_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`worker_profile_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_worker_profiles_matching_survey` (`matching_survey_required_at`,`matching_survey_completed_at`),
  CONSTRAINT `fk_worker_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker_skills` (
  `worker_user_id` int(10) unsigned NOT NULL,
  `skill_id` int(10) unsigned NOT NULL,
  `proficiency_level` enum('beginner','intermediate','advanced') NOT NULL DEFAULT 'intermediate',
  PRIMARY KEY (`worker_user_id`,`skill_id`),
  KEY `idx_worker_skill_lookup` (`skill_id`,`worker_user_id`),
  CONSTRAINT `fk_worker_skill_skill` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`skill_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_worker_skill_worker` FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `worker_work_interests` (
  `worker_user_id` int(10) unsigned NOT NULL,
  `work_interest_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`worker_user_id`,`work_interest_id`),
  KEY `idx_worker_work_interest_lookup` (`work_interest_id`,`worker_user_id`),
  CONSTRAINT `fk_worker_work_interest_interest` FOREIGN KEY (`work_interest_id`) REFERENCES `work_interests` (`work_interest_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_worker_work_interest_worker` FOREIGN KEY (`worker_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

INSERT INTO `job_categories` VALUES (2,'event'),(3,'freelance'),(1,'part_time');

INSERT INTO `work_interests` VALUES (1,'web-development','เขียนโปรแกรมและพัฒนาเว็บไซต์',1,10),(2,'graphic-design','ออกแบบกราฟิกและโปสเตอร์',1,20),(3,'ux-ui-design','ออกแบบ UX/UI',1,30),(4,'video-editing','ตัดต่อวิดีโอ',1,40),(5,'photo-video','ถ่ายภาพและวิดีโอ',1,50),(6,'admin-document','งานเอกสารและธุรการ',1,60),(7,'event-staff','Staff และงานอีเวนต์',1,70),(8,'sales-promotion','งานขายและแนะนำสินค้า',1,80),(9,'food-service','งานบริการ ร้านอาหาร และเครื่องดื่ม',1,90),(10,'content-social','คอนเทนต์และดูแลโซเชียลมีเดีย',1,100);

INSERT INTO `skill_categories` VALUES (1,'งานบริการและร้านค้า','service-retail',10,1,'2026-08-29 09:02:11'),(2,'งานอีเวนต์','event',20,1,'2026-08-29 09:02:11'),(3,'ขายและการตลาด','sales-marketing',30,1,'2026-08-29 09:02:11'),(4,'ครีเอทีฟและดิจิทัล','creative-digital',40,1,'2026-08-29 09:02:11'),(5,'งานสำนักงาน','office',50,1,'2026-08-29 09:02:11'),(6,'เทคโนโลยีและไอที','technology-design',60,1,'2026-08-29 09:02:11'),(7,'ขนส่งและงานทั่วไป','logistics-general',70,1,'2026-08-29 09:02:11');

INSERT INTO `promotion_packages` VALUES (1,'boost-3d','ดันประกาศ 3 วัน','แสดงก่อนประกาศทั่วไปในผลการค้นหา',99.00,3,10,1,10,'2026-09-03 18:44:17','2026-09-03 18:44:17'),(2,'featured-7d','ประกาศแนะนำ 7 วัน','ลำดับสูงกว่าพร้อมป้ายประกาศแนะนำ',199.00,7,20,1,20,'2026-09-03 18:44:17','2026-09-03 18:44:17');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

INSERT INTO `skills` VALUES (158,1,'บริการและดูแลลูกค้า',0,1,NULL,'2026-09-03 18:23:36'),(159,1,'ต้อนรับและให้ข้อมูล',0,1,NULL,'2026-09-03 18:23:36'),(160,1,'งานอาหารและเครื่องดื่ม',0,1,NULL,'2026-09-03 18:23:36'),(161,1,'รับออเดอร์และชำระเงิน',0,1,NULL,'2026-09-03 18:23:36'),(162,1,'จัดการสินค้าในร้าน',0,1,NULL,'2026-09-03 18:23:36'),(163,2,'จัดเตรียมสถานที่',0,1,NULL,'2026-09-03 18:23:36'),(164,2,'ต้อนรับและลงทะเบียน',0,1,NULL,'2026-09-03 18:23:36'),(165,2,'ประสานงานอีเวนต์',0,1,NULL,'2026-09-03 18:23:36'),(166,2,'ดูแลคิวและกิจกรรม',0,1,NULL,'2026-09-03 18:23:36'),(167,2,'ดูแลบูธและผู้ร่วมงาน',0,1,NULL,'2026-09-03 18:23:36'),(168,2,'พิธีกรและการนำเสนอ',0,1,NULL,'2026-09-03 18:23:36'),(169,2,'ดูแลสื่อและอุปกรณ์หน้างาน',0,1,NULL,'2026-09-03 18:23:36'),(170,3,'ขายและแนะนำสินค้า',0,1,NULL,'2026-09-03 18:23:36'),(171,3,'เจรจาและปิดการขาย',0,1,NULL,'2026-09-03 18:23:36'),(172,3,'ขายสินค้าออนไลน์และไลฟ์',0,1,NULL,'2026-09-03 18:23:36'),(173,3,'สร้างคอนเทนต์การตลาด',0,1,NULL,'2026-09-03 18:23:36'),(174,3,'โฆษณาและประชาสัมพันธ์',0,1,NULL,'2026-09-03 18:23:36'),(175,3,'วิเคราะห์ลูกค้าและตลาด',0,1,NULL,'2026-09-03 18:23:36'),(176,4,'ออกแบบกราฟิก',0,1,NULL,'2026-09-03 18:23:36'),(177,4,'ออกแบบเว็บไซต์และ UI/UX',0,1,NULL,'2026-09-03 18:23:36'),(178,4,'ถ่ายและตัดต่อวิดีโอ',0,1,NULL,'2026-09-03 18:23:36'),(179,4,'สร้างคอนเทนต์และเขียนเนื้อหา',0,1,NULL,'2026-09-03 18:23:36'),(180,4,'ภาพประกอบและงาน 3D',0,1,NULL,'2026-09-03 18:23:36'),(181,4,'ผลิตสื่อและงานนำเสนอ',0,1,NULL,'2026-09-03 18:23:36'),(182,5,'จัดทำและจัดเก็บเอกสาร',0,1,NULL,'2026-09-03 18:23:36'),(183,5,'คีย์และจัดการข้อมูล',0,1,NULL,'2026-09-03 18:23:36'),(184,5,'จัดทำตารางและรายงาน',0,1,NULL,'2026-09-03 18:23:36'),(185,5,'รับสายและประสานงาน',0,1,NULL,'2026-09-03 18:23:36'),(186,5,'งานธุรการและสนับสนุน',0,1,NULL,'2026-09-03 18:23:36'),(187,5,'บัญชีและการเงินเบื้องต้น',0,1,NULL,'2026-09-03 18:23:36'),(188,6,'พัฒนาเว็บไซต์',0,1,NULL,'2026-09-03 18:23:36'),(189,6,'พัฒนาแอปพลิเคชันและซอฟต์แวร์',0,1,NULL,'2026-09-03 18:23:36'),(190,6,'จัดการข้อมูลและฐานข้อมูล',0,1,NULL,'2026-09-03 18:23:36'),(191,6,'ดูแลระบบและ IT Support',0,1,NULL,'2026-09-03 18:23:36'),(192,6,'ดูแลเครือข่ายและเซิร์ฟเวอร์',0,1,NULL,'2026-09-03 18:23:36'),(193,6,'ทดสอบระบบและซอฟต์แวร์',0,1,NULL,'2026-09-03 18:23:36'),(194,7,'งานคลังสินค้าและตรวจนับ',0,1,NULL,'2026-09-03 18:23:36'),(195,7,'แพ็กและจัดเตรียมสินค้า',0,1,NULL,'2026-09-03 18:23:36'),(196,7,'ขับรถและขนส่ง',0,1,NULL,'2026-09-03 18:23:36'),(197,7,'ติดตั้งและเคลื่อนย้ายอุปกรณ์',0,1,NULL,'2026-09-03 18:23:36'),(198,7,'งานช่างและซ่อมบำรุง',0,1,NULL,'2026-09-03 18:23:36'),(199,7,'งานใช้แรงและงานทั่วไป',0,1,NULL,'2026-09-03 18:23:36'),(200,7,'ดูแลสถานที่และความสะอาด',0,1,NULL,'2026-09-03 18:23:36');
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

