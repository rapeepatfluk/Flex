ALTER TABLE applications
  ADD COLUMN completed_at DATETIME NULL AFTER application_status,
  ADD KEY idx_application_completed_at (completed_at);
