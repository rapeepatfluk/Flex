-- FLEXJOB initial schema (generated from the current canonical schema)
-- Apply only to a fresh database.

CREATE TABLE users (
  user_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin', 'employer', 'worker') NOT NULL,
  phone VARCHAR(30) NULL,
  account_status ENUM('active', 'suspended', 'pending') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  email_verified_at TIMESTAMP NULL DEFAULT NULL
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
  application_status ENUM('submitted', 'eligible', 'interview_passed', 'completed', 'not_selected', 'withdrawn') NOT NULL DEFAULT 'submitted',
  withdrawn_at DATETIME NULL,
  rating_by_worker TINYINT UNSIGNED NULL,
  rated_by_worker_at TIMESTAMP NULL DEFAULT NULL,
  rating_by_employer TINYINT UNSIGNED NULL,
  rated_by_employer_at TIMESTAMP NULL DEFAULT NULL,
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

CREATE TABLE skill_categories (
  skill_category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(100) NOT NULL,
  category_slug VARCHAR(100) NOT NULL UNIQUE,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE skills (
  skill_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  skill_category_id INT UNSIGNED NULL,
  skill_name VARCHAR(100) NOT NULL UNIQUE,
  is_custom TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_skill_category FOREIGN KEY (skill_category_id) REFERENCES skill_categories(skill_category_id) ON DELETE SET NULL,
  INDEX idx_skill_catalog (skill_category_id, is_active)
) ENGINE=InnoDB;
INSERT INTO skill_categories (category_name, category_slug, sort_order) VALUES
  ('งานบริการและร้านค้า', 'service-retail', 10),
  ('งานอีเวนต์', 'event', 20),
  ('ขายและการตลาด', 'sales-marketing', 30),
  ('ครีเอทีฟและดิจิทัล', 'creative-digital', 40),
  ('งานสำนักงาน', 'office', 50),
  ('เทคโนโลยีและการออกแบบ', 'technology-design', 60),
  ('ขนส่งและงานทั่วไป', 'logistics-general', 70)
ON DUPLICATE KEY UPDATE category_name=VALUES(category_name), sort_order=VALUES(sort_order), is_active=1;

INSERT INTO skills (skill_name, skill_category_id, is_custom, is_active)
SELECT seed.skill_name, category.skill_category_id, 0, 1
FROM (
  SELECT 'บริการลูกค้า' AS skill_name, 'service-retail' AS category_slug UNION ALL SELECT 'รับออเดอร์', 'service-retail' UNION ALL SELECT 'แคชเชียร์', 'service-retail' UNION ALL SELECT 'ชงกาแฟ', 'service-retail' UNION ALL SELECT 'จัดเรียงสินค้า', 'service-retail' UNION ALL SELECT 'ใช้ระบบ POS', 'service-retail' UNION ALL SELECT 'ทำอาหาร', 'service-retail' UNION ALL SELECT 'ตอบแชตลูกค้า', 'service-retail' UNION ALL
  SELECT 'ลงทะเบียนหน้างาน', 'event' UNION ALL SELECT 'ดูแลบูธ', 'event' UNION ALL SELECT 'ประสานงาน', 'event' UNION ALL SELECT 'จัดสถานที่', 'event' UNION ALL SELECT 'MC / พิธีกร', 'event' UNION ALL SELECT 'ดูแลเครื่องเสียง', 'event' UNION ALL SELECT 'ถ่ายภาพหน้างาน', 'event' UNION ALL SELECT 'ควบคุมคิว', 'event' UNION ALL
  SELECT 'การขาย', 'sales-marketing' UNION ALL SELECT 'ปิดการขาย', 'sales-marketing' UNION ALL SELECT 'Live ขายสินค้า', 'sales-marketing' UNION ALL SELECT 'Canva', 'sales-marketing' UNION ALL SELECT 'ดูแลโซเชียลมีเดีย', 'sales-marketing' UNION ALL SELECT 'ยิงโฆษณาออนไลน์', 'sales-marketing' UNION ALL SELECT 'ทำคอนเทนต์', 'sales-marketing' UNION ALL SELECT 'วิเคราะห์ลูกค้า', 'sales-marketing' UNION ALL
  SELECT 'Photoshop', 'creative-digital' UNION ALL SELECT 'Illustrator', 'creative-digital' UNION ALL SELECT 'ตัดต่อวิดีโอ', 'creative-digital' UNION ALL SELECT 'ถ่ายภาพ', 'creative-digital' UNION ALL SELECT 'เขียนคอนเทนต์', 'creative-digital' UNION ALL SELECT 'Motion Graphic', 'creative-digital' UNION ALL SELECT '3D Design', 'creative-digital' UNION ALL SELECT 'CapCut', 'creative-digital' UNION ALL
  SELECT 'Microsoft Excel', 'office' UNION ALL SELECT 'Google Workspace', 'office' UNION ALL SELECT 'คีย์ข้อมูล', 'office' UNION ALL SELECT 'จัดเอกสาร', 'office' UNION ALL SELECT 'พิมพ์เอกสาร', 'office' UNION ALL SELECT 'จัดตารางนัดหมาย', 'office' UNION ALL SELECT 'รับโทรศัพท์', 'office' UNION ALL SELECT 'ธุรการ', 'office' UNION ALL
  SELECT 'HTML / CSS', 'technology-design' UNION ALL SELECT 'JavaScript', 'technology-design' UNION ALL SELECT 'PHP', 'technology-design' UNION ALL SELECT 'MySQL / Database', 'technology-design' UNION ALL SELECT 'Front-end Development', 'technology-design' UNION ALL SELECT 'Back-end Development', 'technology-design' UNION ALL SELECT 'Full-stack Development', 'technology-design' UNION ALL SELECT 'WordPress', 'technology-design' UNION ALL SELECT 'UI / UX Design', 'technology-design' UNION ALL SELECT 'Figma', 'technology-design' UNION ALL SELECT 'Web Design', 'technology-design' UNION ALL SELECT 'Graphic Design', 'technology-design' UNION ALL
  SELECT 'ขับรถยนต์', 'logistics-general' UNION ALL SELECT 'ขับมอเตอร์ไซค์', 'logistics-general' UNION ALL SELECT 'แพ็กสินค้า', 'logistics-general' UNION ALL SELECT 'ยกของ', 'logistics-general' UNION ALL SELECT 'ใช้เครื่องมือช่าง', 'logistics-general' UNION ALL SELECT 'ติดตั้งอุปกรณ์', 'logistics-general' UNION ALL SELECT 'ตรวจนับสินค้า', 'logistics-general' UNION ALL SELECT 'จัดส่งสินค้า', 'logistics-general'
) AS seed
JOIN skill_categories AS category ON category.category_slug = seed.category_slug
ON DUPLICATE KEY UPDATE skill_category_id = COALESCE(skills.skill_category_id, VALUES(skill_category_id)), is_active = 1;


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

-- Tokens for email verification and password recovery
CREATE TABLE auth_tokens (
  auth_token_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token CHAR(64) NOT NULL UNIQUE,
  token_type ENUM('email_verification', 'password_reset') NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_auth_token_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_auth_token_lookup (user_id, token_type, used_at)
) ENGINE=InnoDB;

CREATE TABLE email_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  to_email VARCHAR(190) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  status ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
  error_msg TEXT NULL,
  sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_log_recipient (to_email)
) ENGINE=InnoDB;
