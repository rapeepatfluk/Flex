CREATE TABLE promotion_packages (
  package_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  package_code VARCHAR(50) NOT NULL UNIQUE,
  package_name VARCHAR(120) NOT NULL,
  package_description VARCHAR(255) NULL,
  price DECIMAL(10,2) NOT NULL,
  duration_days SMALLINT UNSIGNED NOT NULL,
  display_priority SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO promotion_packages
  (package_code,package_name,package_description,price,duration_days,display_priority,sort_order)
VALUES
  ('boost-3d','ดันประกาศ 3 วัน','แสดงก่อนประกาศทั่วไปในผลการค้นหา',99.00,3,10,10),
  ('featured-7d','ประกาศแนะนำ 7 วัน','ลำดับสูงกว่าพร้อมป้ายประกาศแนะนำ',199.00,7,20,20);

CREATE TABLE job_promotions (
  promotion_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id INT UNSIGNED NOT NULL,
  employer_user_id INT UNSIGNED NOT NULL,
  package_id INT UNSIGNED NOT NULL,
  package_name_snapshot VARCHAR(120) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  duration_days SMALLINT UNSIGNED NOT NULL,
  promotion_status ENUM('pending_payment','pending_verification','active','rejected','expired','cancelled') NOT NULL DEFAULT 'pending_payment',
  payment_slip_path VARCHAR(255) NULL,
  payment_reference VARCHAR(120) NULL,
  payment_submitted_at DATETIME NULL,
  reviewed_by_user_id INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  review_note TEXT NULL,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_job_promotion_job FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
  CONSTRAINT fk_job_promotion_employer FOREIGN KEY (employer_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_job_promotion_package FOREIGN KEY (package_id) REFERENCES promotion_packages(package_id),
  CONSTRAINT fk_job_promotion_reviewer FOREIGN KEY (reviewed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
  INDEX idx_promotion_job_status (job_id,promotion_status,ends_at),
  INDEX idx_promotion_review_queue (promotion_status,payment_submitted_at),
  INDEX idx_promotion_employer (employer_user_id,created_at)
) ENGINE=InnoDB;
