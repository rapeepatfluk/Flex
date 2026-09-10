CREATE TABLE subscription_plans (
  plan_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  plan_code VARCHAR(50) NOT NULL,
  plan_name VARCHAR(120) NOT NULL,
  plan_description VARCHAR(255) NULL,
  price DECIMAL(10,2) NOT NULL,
  duration_days SMALLINT UNSIGNED NOT NULL,
  active_job_limit SMALLINT UNSIGNED NOT NULL,
  promotion_credits SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  promotion_duration_days SMALLINT UNSIGNED NOT NULL DEFAULT 7,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 10,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (plan_id),
  UNIQUE KEY uq_subscription_plan_code (plan_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO subscription_plans
  (plan_code,plan_name,plan_description,price,duration_days,active_job_limit,promotion_credits,promotion_duration_days,sort_order)
VALUES
  ('pro-30d','Pro 30 วัน','เปิดรับพร้อมกัน 6 ประกาศ และโปรโมตได้ 2 ครั้ง ครั้งละ 7 วัน',239.00,30,6,2,7,10);

CREATE TABLE employer_subscriptions (
  subscription_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  employer_user_id INT UNSIGNED NOT NULL,
  plan_id INT UNSIGNED NOT NULL,
  plan_name_snapshot VARCHAR(120) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  duration_days SMALLINT UNSIGNED NOT NULL,
  active_job_limit SMALLINT UNSIGNED NOT NULL,
  promotion_credits SMALLINT UNSIGNED NOT NULL,
  promotion_duration_days SMALLINT UNSIGNED NOT NULL,
  subscription_status ENUM('pending_payment','pending_verification','active','rejected','expired','cancelled') NOT NULL DEFAULT 'pending_payment',
  payment_slip_path VARCHAR(255) NULL,
  payment_reference VARCHAR(120) NULL,
  payment_submitted_at DATETIME NULL,
  reviewed_by_user_id INT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  review_note VARCHAR(1000) NULL,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (subscription_id),
  KEY idx_subscription_employer_period (employer_user_id,subscription_status,starts_at,ends_at),
  KEY idx_subscription_review_queue (subscription_status,payment_submitted_at),
  CONSTRAINT fk_subscription_employer FOREIGN KEY (employer_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_subscription_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(plan_id),
  CONSTRAINT fk_subscription_reviewer FOREIGN KEY (reviewed_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE job_promotions
  ADD COLUMN subscription_id INT UNSIGNED NULL AFTER employer_user_id,
  ADD COLUMN promotion_source ENUM('standalone','subscription') NOT NULL DEFAULT 'standalone' AFTER subscription_id,
  ADD KEY idx_promotion_subscription (subscription_id,promotion_status),
  ADD CONSTRAINT fk_job_promotion_subscription FOREIGN KEY (subscription_id) REFERENCES employer_subscriptions(subscription_id) ON DELETE SET NULL;

INSERT INTO promotion_packages
  (package_code,package_name,package_description,price,duration_days,display_priority,is_active,sort_order)
VALUES
  ('pro-credit-7d','สิทธิ์โปรโมตจาก Pro','สิทธิ์โปรโมต 7 วันจากแพ็กเกจ Pro',0.00,7,20,0,99)
ON DUPLICATE KEY UPDATE is_active=0;

UPDATE promotion_packages
SET is_active=0
WHERE package_code IN ('boost-3d','featured-7d');

UPDATE jobs
SET application_deadline=DATE_ADD(CURDATE(),INTERVAL 30 DAY)
WHERE job_status='published' AND application_deadline IS NULL;
