-- FLEXJOB consolidated database migration
-- Target: upgrade an existing FLEXJOB database for the current application code.
-- Date: 2026-08-27
--
-- Safety:
--   * Back up the database before importing this file.
--   * This migration does not DROP tables or DELETE existing records.
--   * Existing jobs are NOT assigned a work interest automatically.
--   * The script is designed for MariaDB 10.4+ as bundled with XAMPP.
--   * It can be imported again when a previous import was interrupted.

USE db_flexjob;
SET NAMES utf8mb4;

-- --------------------------------------------------------------------------
-- 1. Email verification and password recovery
-- --------------------------------------------------------------------------

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMP NULL DEFAULT NULL AFTER created_at;

ALTER TABLE users
  MODIFY COLUMN account_status ENUM('active','suspended','pending') NOT NULL DEFAULT 'active';

CREATE TABLE IF NOT EXISTS email_verifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_email_verification_token (token),
  KEY idx_email_verification_user (user_id),
  CONSTRAINT fk_email_verification_user
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS email_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  to_email VARCHAR(190) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  status ENUM('sent','failed') NOT NULL DEFAULT 'sent',
  error_msg TEXT NULL,
  sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_email_log_recipient (to_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_password_reset_token (token),
  KEY idx_password_reset_user (user_id),
  CONSTRAINT fk_password_reset_user
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------------
-- 2. Worker profile discovery and job matching fields
-- --------------------------------------------------------------------------

ALTER TABLE worker_profiles
  ADD COLUMN IF NOT EXISTS profile_image_path VARCHAR(255) NULL AFTER biography,
  ADD COLUMN IF NOT EXISTS profile_visibility ENUM('application_only','searchable') NOT NULL DEFAULT 'application_only' AFTER portfolio_url,
  ADD COLUMN IF NOT EXISTS work_province VARCHAR(100) NULL AFTER profile_visibility,
  ADD COLUMN IF NOT EXISTS preferred_work_mode ENUM('any','onsite','remote','hybrid') NOT NULL DEFAULT 'any' AFTER work_province,
  ADD COLUMN IF NOT EXISTS available_from DATE NULL AFTER preferred_work_mode,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER available_from;

ALTER TABLE jobs
  ADD COLUMN IF NOT EXISTS work_mode ENUM('onsite','remote','hybrid') NOT NULL DEFAULT 'onsite' AFTER work_schedule,
  ADD COLUMN IF NOT EXISTS application_deadline DATE NULL AFTER work_mode,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

ALTER TABLE applications
  MODIFY COLUMN application_status ENUM('submitted','eligible','not_selected','withdrawn') NOT NULL DEFAULT 'submitted',
  ADD COLUMN IF NOT EXISTS withdrawn_at DATETIME NULL AFTER application_status;

-- --------------------------------------------------------------------------
-- 3. Structured skills and two-way matching
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS skills (
  skill_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  skill_name VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_skill_name (skill_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS worker_skills (
  worker_user_id INT UNSIGNED NOT NULL,
  skill_id INT UNSIGNED NOT NULL,
  proficiency_level ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'intermediate',
  PRIMARY KEY (worker_user_id, skill_id),
  KEY idx_worker_skill_lookup (skill_id, worker_user_id),
  CONSTRAINT fk_worker_skill_worker
    FOREIGN KEY (worker_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_worker_skill_skill
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS job_skills (
  job_id INT UNSIGNED NOT NULL,
  skill_id INT UNSIGNED NOT NULL,
  importance ENUM('required','preferred') NOT NULL DEFAULT 'required',
  PRIMARY KEY (job_id, skill_id),
  KEY idx_job_skill_lookup (skill_id, importance, job_id),
  CONSTRAINT fk_job_skill_job
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
  CONSTRAINT fk_job_skill_skill
    FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS worker_job_preferences (
  worker_user_id INT UNSIGNED NOT NULL,
  job_category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (worker_user_id, job_category_id),
  KEY idx_worker_preference_category (job_category_id, worker_user_id),
  CONSTRAINT fk_worker_preference_worker
    FOREIGN KEY (worker_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_worker_preference_category
    FOREIGN KEY (job_category_id) REFERENCES job_categories(job_category_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS job_invitations (
  job_invitation_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id INT UNSIGNED NOT NULL,
  worker_user_id INT UNSIGNED NOT NULL,
  invitation_message TEXT NULL,
  invitation_status ENUM('sent','viewed','accepted','declined') NOT NULL DEFAULT 'sent',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  responded_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uq_job_invitation_worker (job_id, worker_user_id),
  KEY idx_worker_invitation_inbox (worker_user_id, invitation_status, created_at),
  CONSTRAINT fk_job_invitation_job
    FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
  CONSTRAINT fk_job_invitation_worker
    FOREIGN KEY (worker_user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------------------------
-- 4. Curated work interests used by the matching survey
-- --------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS work_interests (
  work_interest_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  interest_slug VARCHAR(100) NOT NULL,
  interest_name VARCHAR(180) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY uq_work_interest_slug (interest_slug),
  KEY idx_work_interest_active (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO work_interests (interest_slug, interest_name, sort_order) VALUES
('web-development', 'เขียนโปรแกรมและพัฒนาเว็บไซต์', 10),
('graphic-design', 'ออกแบบกราฟิกและโปสเตอร์', 20),
('ux-ui-design', 'ออกแบบ UX/UI', 30),
('video-editing', 'ตัดต่อวิดีโอ', 40),
('photo-video', 'ถ่ายภาพและวิดีโอ', 50),
('admin-document', 'งานเอกสารและธุรการ', 60),
('event-staff', 'Staff และงานอีเวนต์', 70),
('sales-promotion', 'งานขายและแนะนำสินค้า', 80),
('food-service', 'งานบริการ ร้านอาหาร และเครื่องดื่ม', 90),
('content-social', 'คอนเทนต์และดูแลโซเชียลมีเดีย', 100)
ON DUPLICATE KEY UPDATE
  interest_name=VALUES(interest_name),
  sort_order=VALUES(sort_order);

CREATE TABLE IF NOT EXISTS worker_work_interests (
  worker_user_id INT UNSIGNED NOT NULL,
  work_interest_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (worker_user_id, work_interest_id),
  KEY idx_worker_work_interest_lookup (work_interest_id, worker_user_id),
  CONSTRAINT fk_worker_work_interest_worker
    FOREIGN KEY (worker_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_worker_work_interest_interest
    FOREIGN KEY (work_interest_id) REFERENCES work_interests(work_interest_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE jobs
  ADD COLUMN IF NOT EXISTS work_interest_id INT UNSIGNED NULL AFTER job_category_id;

-- MariaDB does not support ADD CONSTRAINT IF NOT EXISTS on all XAMPP versions.
-- Check information_schema first so this section can safely be run again.
SET @flexjob_work_interest_fk_exists = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA=DATABASE()
    AND TABLE_NAME='jobs'
    AND CONSTRAINT_NAME='fk_job_work_interest'
    AND CONSTRAINT_TYPE='FOREIGN KEY'
);

SET @flexjob_work_interest_fk_sql = IF(
  @flexjob_work_interest_fk_exists=0,
  'ALTER TABLE jobs ADD CONSTRAINT fk_job_work_interest FOREIGN KEY (work_interest_id) REFERENCES work_interests(work_interest_id) ON DELETE SET NULL',
  'SELECT 1'
);

PREPARE flexjob_work_interest_fk_statement FROM @flexjob_work_interest_fk_sql;
EXECUTE flexjob_work_interest_fk_statement;
DEALLOCATE PREPARE flexjob_work_interest_fk_statement;

-- --------------------------------------------------------------------------
-- 5. Verification report (read-only)
-- --------------------------------------------------------------------------

SELECT
  'FLEXJOB migration completed' AS migration_status,
  (SELECT COUNT(*) FROM work_interests WHERE is_active=1) AS active_work_interests,
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE()
      AND TABLE_NAME='worker_profiles'
      AND COLUMN_NAME='profile_visibility') AS profile_visibility_column,
  (SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE()
      AND TABLE_NAME='jobs'
      AND COLUMN_NAME='work_interest_id') AS work_interest_column;

