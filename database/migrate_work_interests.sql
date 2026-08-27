-- FLEXJOB: granular work interests for two-way matching
-- Additive migration. Existing jobs stay unclassified until reviewed manually.

USE db_flexjob;

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
  CONSTRAINT fk_worker_work_interest_worker FOREIGN KEY (worker_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_worker_work_interest_interest FOREIGN KEY (work_interest_id) REFERENCES work_interests(work_interest_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE jobs
  ADD COLUMN IF NOT EXISTS work_interest_id INT UNSIGNED NULL AFTER job_category_id;

SET @work_interest_fk_exists = (
  SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA=DATABASE()
    AND TABLE_NAME='jobs'
    AND CONSTRAINT_NAME='fk_job_work_interest'
    AND CONSTRAINT_TYPE='FOREIGN KEY'
);
SET @work_interest_fk_sql = IF(
  @work_interest_fk_exists=0,
  'ALTER TABLE jobs ADD CONSTRAINT fk_job_work_interest FOREIGN KEY (work_interest_id) REFERENCES work_interests(work_interest_id) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE work_interest_fk_statement FROM @work_interest_fk_sql;
EXECUTE work_interest_fk_statement;
DEALLOCATE PREPARE work_interest_fk_statement;

