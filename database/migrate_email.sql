-- Email Verification & Notification System Migration

-- 1. Add email_verified_at to users
ALTER TABLE users 
  ADD COLUMN IF NOT EXISTS email_verified_at TIMESTAMP NULL DEFAULT NULL AFTER created_at;

-- 2. Add 'pending' status to account_status enum
ALTER TABLE users 
  MODIFY COLUMN account_status ENUM('active','suspended','pending') NOT NULL DEFAULT 'active';

-- 3. Email verifications table
CREATE TABLE IF NOT EXISTS email_verifications (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  token        CHAR(64) NOT NULL UNIQUE,
  expires_at   DATETIME NOT NULL,
  used_at      TIMESTAMP NULL DEFAULT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  INDEX idx_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Email log table (optional - for auditing sent emails)
CREATE TABLE IF NOT EXISTS email_log (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  to_email   VARCHAR(190) NOT NULL,
  subject    VARCHAR(255) NOT NULL,
  status     ENUM('sent','failed') NOT NULL DEFAULT 'sent',
  error_msg  TEXT NULL,
  sent_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_to (to_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
