CREATE TABLE legacy_standalone_job_promotions LIKE job_promotions;

INSERT INTO legacy_standalone_job_promotions
SELECT *
FROM job_promotions
WHERE subscription_id IS NULL OR promotion_source='standalone';

DELETE FROM job_promotions
WHERE subscription_id IS NULL OR promotion_source='standalone';

ALTER TABLE job_promotions
  DROP FOREIGN KEY fk_job_promotion_package,
  DROP FOREIGN KEY fk_job_promotion_reviewer,
  DROP FOREIGN KEY fk_job_promotion_subscription;

ALTER TABLE job_promotions
  DROP COLUMN promotion_source,
  DROP COLUMN package_id,
  DROP COLUMN package_name_snapshot,
  DROP COLUMN amount,
  DROP COLUMN payment_slip_path,
  DROP COLUMN payment_reference,
  DROP COLUMN payment_submitted_at,
  DROP COLUMN reviewed_by_user_id,
  DROP COLUMN reviewed_at,
  DROP COLUMN review_note,
  MODIFY subscription_id INT UNSIGNED NOT NULL,
  MODIFY promotion_status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
  MODIFY starts_at DATETIME NOT NULL,
  MODIFY ends_at DATETIME NOT NULL,
  ADD CONSTRAINT fk_job_promotion_subscription
    FOREIGN KEY (subscription_id) REFERENCES employer_subscriptions(subscription_id) ON DELETE CASCADE;

DROP TABLE promotion_packages;
