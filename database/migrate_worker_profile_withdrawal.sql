-- FLEXJOB: worker profile photo and application withdrawal
-- Additive migration. Existing applications and profile data are preserved.

USE db_flexjob;

ALTER TABLE worker_profiles
  ADD COLUMN IF NOT EXISTS profile_image_path VARCHAR(255) NULL AFTER biography;

ALTER TABLE applications
  MODIFY COLUMN application_status ENUM('submitted','eligible','not_selected','withdrawn') NOT NULL DEFAULT 'submitted',
  ADD COLUMN IF NOT EXISTS withdrawn_at DATETIME NULL AFTER application_status;
