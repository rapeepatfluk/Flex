-- Keep token expiry immutable when used_at is updated.
USE db_flexjob;

ALTER TABLE email_verifications
  MODIFY expires_at DATETIME NOT NULL;

ALTER TABLE password_resets
  MODIFY expires_at DATETIME NOT NULL;
