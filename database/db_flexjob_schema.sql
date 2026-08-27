CREATE DATABASE IF NOT EXISTS db_flexjob CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_flexjob;
SET NAMES utf8mb4;

-- ตารางเดิมไม่มีข้อมูลและถูกแทนที่ด้วย users ตามโครงสร้างใหม่
DROP TABLE IF EXISTS `user`;

CREATE TABLE users (
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'employer', 'worker') NOT NULL,
  phone VARCHAR(30) NULL,
  account_status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE employer_profiles (
  employer_profile_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  company_name VARCHAR(180) NOT NULL,
  company_description TEXT NULL,
  company_address TEXT NULL,
  company_logo_path VARCHAR(255) NULL,
  CONSTRAINT fk_employer_profile_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE worker_profiles (
  worker_profile_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  professional_headline VARCHAR(180) NULL,
  biography TEXT NULL,
  profile_image_path VARCHAR(255) NULL,
  skills TEXT NULL,
  resume_file_path VARCHAR(255) NULL,
  portfolio_file_path VARCHAR(255) NULL,
  portfolio_url VARCHAR(500) NULL,
  profile_visibility ENUM('application_only', 'searchable') NOT NULL DEFAULT 'application_only',
  work_province VARCHAR(100) NULL,
  preferred_work_mode ENUM('any', 'onsite', 'remote', 'hybrid') NOT NULL DEFAULT 'any',
  available_from DATE NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_worker_profile_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE employer_documents (
  employer_document_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employer_user_id INT UNSIGNED NOT NULL,
  document_file_path VARCHAR(255) NOT NULL,
  document_status ENUM('pending', 'approved', 'rejected', 'resubmit') NOT NULL DEFAULT 'pending',
  review_note TEXT NULL,
  reviewed_by_user_id INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_employer_document_employer FOREIGN KEY (employer_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_employer_document_reviewer FOREIGN KEY (reviewed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
  INDEX idx_employer_document_status (employer_user_id, document_status)
) ENGINE=InnoDB;

CREATE TABLE job_categories (
  job_category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_slug VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO job_categories (category_slug) VALUES
('part_time'),
('event'),
('freelance');

CREATE TABLE work_interests (
  work_interest_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  interest_slug VARCHAR(100) NOT NULL UNIQUE,
  interest_name VARCHAR(180) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  INDEX idx_work_interest_active (is_active, sort_order)
) ENGINE=InnoDB;

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
('content-social', 'คอนเทนต์และดูแลโซเชียลมีเดีย', 100);

CREATE TABLE jobs (
  job_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employer_user_id INT UNSIGNED NOT NULL,
  job_category_id INT UNSIGNED NOT NULL,
  work_interest_id INT UNSIGNED NULL,
  job_title VARCHAR(180) NOT NULL,
  job_description TEXT NOT NULL,
  work_location VARCHAR(180) NOT NULL,
  work_province VARCHAR(100) NULL,
  work_schedule VARCHAR(180) NULL,
  work_mode ENUM('onsite', 'remote', 'hybrid') NOT NULL DEFAULT 'onsite',
  application_deadline DATE NULL,
  pay_amount DECIMAL(10,2) NOT NULL,
  pay_unit ENUM('hour', 'day', 'project') NOT NULL DEFAULT 'day',
  open_positions SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  job_status ENUM('published', 'hidden', 'closed') NOT NULL DEFAULT 'published',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_job_employer FOREIGN KEY (employer_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_job_category FOREIGN KEY (job_category_id) REFERENCES job_categories(job_category_id),
  CONSTRAINT fk_job_work_interest FOREIGN KEY (work_interest_id) REFERENCES work_interests(work_interest_id) ON DELETE SET NULL,
  INDEX idx_job_listing (job_status, job_category_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE job_images (
  job_image_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id INT UNSIGNED NOT NULL,
  image_file_path VARCHAR(255) NOT NULL,
  display_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  CONSTRAINT fk_job_image_job FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
  INDEX idx_job_image_order (job_id, display_order)
) ENGINE=InnoDB;

CREATE TABLE applications (
  application_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id INT UNSIGNED NOT NULL,
  worker_user_id INT UNSIGNED NOT NULL,
  resume_file_path VARCHAR(255) NULL,
  cover_note TEXT NULL,
  application_status ENUM('submitted', 'eligible', 'not_selected', 'withdrawn') NOT NULL DEFAULT 'submitted',
  withdrawn_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_application_job FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
  CONSTRAINT fk_application_worker FOREIGN KEY (worker_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT uq_application_job_worker UNIQUE (job_id, worker_user_id),
  INDEX idx_application_worker (worker_user_id, application_status)
) ENGINE=InnoDB;

CREATE TABLE notifications (
  notification_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  notification_title VARCHAR(180) NOT NULL,
  notification_message TEXT NOT NULL,
  notification_url VARCHAR(255) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_notification_inbox (user_id, is_read, created_at)
) ENGINE=InnoDB;

CREATE TABLE skills (
  skill_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  skill_name VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE worker_skills (
  worker_user_id INT UNSIGNED NOT NULL,
  skill_id INT UNSIGNED NOT NULL,
  proficiency_level ENUM('beginner', 'intermediate', 'advanced') NOT NULL DEFAULT 'intermediate',
  PRIMARY KEY (worker_user_id, skill_id),
  CONSTRAINT fk_worker_skill_worker FOREIGN KEY (worker_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_worker_skill_skill FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE,
  INDEX idx_worker_skill_lookup (skill_id, worker_user_id)
) ENGINE=InnoDB;

CREATE TABLE job_skills (
  job_id INT UNSIGNED NOT NULL,
  skill_id INT UNSIGNED NOT NULL,
  importance ENUM('required', 'preferred') NOT NULL DEFAULT 'required',
  PRIMARY KEY (job_id, skill_id),
  CONSTRAINT fk_job_skill_job FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
  CONSTRAINT fk_job_skill_skill FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE,
  INDEX idx_job_skill_lookup (skill_id, importance, job_id)
) ENGINE=InnoDB;

CREATE TABLE worker_job_preferences (
  worker_user_id INT UNSIGNED NOT NULL,
  job_category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (worker_user_id, job_category_id),
  CONSTRAINT fk_worker_preference_worker FOREIGN KEY (worker_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_worker_preference_category FOREIGN KEY (job_category_id) REFERENCES job_categories(job_category_id) ON DELETE CASCADE,
  INDEX idx_worker_preference_category (job_category_id, worker_user_id)
) ENGINE=InnoDB;

CREATE TABLE worker_work_interests (
  worker_user_id INT UNSIGNED NOT NULL,
  work_interest_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (worker_user_id, work_interest_id),
  CONSTRAINT fk_worker_work_interest_worker FOREIGN KEY (worker_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_worker_work_interest_interest FOREIGN KEY (work_interest_id) REFERENCES work_interests(work_interest_id) ON DELETE CASCADE,
  INDEX idx_worker_work_interest_lookup (work_interest_id, worker_user_id)
) ENGINE=InnoDB;

CREATE TABLE job_invitations (
  job_invitation_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id INT UNSIGNED NOT NULL,
  worker_user_id INT UNSIGNED NOT NULL,
  invitation_message TEXT NULL,
  invitation_status ENUM('sent', 'viewed', 'accepted', 'declined') NOT NULL DEFAULT 'sent',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  responded_at TIMESTAMP NULL DEFAULT NULL,
  CONSTRAINT fk_job_invitation_job FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
  CONSTRAINT fk_job_invitation_worker FOREIGN KEY (worker_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  UNIQUE KEY uq_job_invitation_worker (job_id, worker_user_id),
  INDEX idx_worker_invitation_inbox (worker_user_id, invitation_status, created_at)
) ENGINE=InnoDB;
