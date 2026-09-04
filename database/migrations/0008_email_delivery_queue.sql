ALTER TABLE email_log
  MODIFY COLUMN status ENUM('queued','processing','sent','failed') NOT NULL DEFAULT 'queued',
  ADD COLUMN to_name VARCHAR(190) NULL AFTER to_email,
  ADD COLUMN html_body LONGTEXT NULL AFTER subject,
  ADD COLUMN reply_to_email VARCHAR(190) NULL AFTER html_body,
  ADD COLUMN reply_to_name VARCHAR(190) NULL AFTER reply_to_email,
  ADD COLUMN attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER error_msg,
  ADD COLUMN available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER attempts,
  ADD COLUMN locked_at DATETIME NULL AFTER available_at,
  MODIFY COLUMN sent_at TIMESTAMP NULL DEFAULT NULL,
  ADD INDEX idx_email_log_delivery_queue (status, available_at);
