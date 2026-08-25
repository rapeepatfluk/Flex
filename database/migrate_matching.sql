-- FLEXJOB: structured two-way job matching
-- Additive migration: existing columns and workflows remain available.

USE db_flexjob;

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
  CONSTRAINT fk_worker_skill_worker FOREIGN KEY (worker_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_worker_skill_skill FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS job_skills (
  job_id INT UNSIGNED NOT NULL,
  skill_id INT UNSIGNED NOT NULL,
  importance ENUM('required','preferred') NOT NULL DEFAULT 'required',
  PRIMARY KEY (job_id, skill_id),
  KEY idx_job_skill_lookup (skill_id, importance, job_id),
  CONSTRAINT fk_job_skill_job FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
  CONSTRAINT fk_job_skill_skill FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS worker_job_preferences (
  worker_user_id INT UNSIGNED NOT NULL,
  job_category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (worker_user_id, job_category_id),
  KEY idx_worker_preference_category (job_category_id, worker_user_id),
  CONSTRAINT fk_worker_preference_worker FOREIGN KEY (worker_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_worker_preference_category FOREIGN KEY (job_category_id) REFERENCES job_categories(job_category_id) ON DELETE CASCADE
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
  CONSTRAINT fk_job_invitation_job FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
  CONSTRAINT fk_job_invitation_worker FOREIGN KEY (worker_user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE worker_profiles
  ADD COLUMN IF NOT EXISTS profile_visibility ENUM('application_only','searchable') NOT NULL DEFAULT 'application_only' AFTER portfolio_url,
  ADD COLUMN IF NOT EXISTS work_province VARCHAR(100) NULL AFTER profile_visibility,
  ADD COLUMN IF NOT EXISTS preferred_work_mode ENUM('any','onsite','remote','hybrid') NOT NULL DEFAULT 'any' AFTER work_province,
  ADD COLUMN IF NOT EXISTS available_from DATE NULL AFTER preferred_work_mode,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER available_from;

ALTER TABLE jobs
  ADD COLUMN IF NOT EXISTS work_mode ENUM('onsite','remote','hybrid') NOT NULL DEFAULT 'onsite' AFTER work_schedule,
  ADD COLUMN IF NOT EXISTS application_deadline DATE NULL AFTER work_mode,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

