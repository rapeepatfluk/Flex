USE db_flexjob;

-- Combine two one-per-application ratings into the applications table.
ALTER TABLE applications
  ADD COLUMN rating_by_worker TINYINT UNSIGNED NULL AFTER withdrawn_at,
  ADD COLUMN rated_by_worker_at TIMESTAMP NULL DEFAULT NULL AFTER rating_by_worker,
  ADD COLUMN rating_by_employer TINYINT UNSIGNED NULL AFTER rated_by_worker_at,
  ADD COLUMN rated_by_employer_at TIMESTAMP NULL DEFAULT NULL AFTER rating_by_employer;

UPDATE applications a
JOIN application_ratings r ON r.application_id = a.application_id
SET a.rating_by_worker = r.rating,
    a.rated_by_worker_at = r.created_at
WHERE r.rater_user_id = a.worker_user_id;

UPDATE applications a
JOIN jobs j ON j.job_id = a.job_id
JOIN application_ratings r ON r.application_id = a.application_id
SET a.rating_by_employer = r.rating,
    a.rated_by_employer_at = r.created_at
WHERE r.rater_user_id = j.employer_user_id;

DROP TABLE application_ratings;

-- Combine email-verification and password-reset tokens into one table.
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

INSERT INTO auth_tokens (user_id, token, token_type, expires_at, used_at, created_at)
SELECT user_id, token, 'email_verification', expires_at, used_at, created_at FROM email_verifications;

INSERT INTO auth_tokens (user_id, token, token_type, expires_at, used_at, created_at)
SELECT user_id, token, 'password_reset', expires_at, used_at, created_at FROM password_resets;

DROP TABLE email_verifications;
DROP TABLE password_resets;