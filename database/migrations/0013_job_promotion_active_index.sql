ALTER TABLE job_promotions
  DROP INDEX idx_promotion_review_queue,
  ADD KEY idx_promotion_active_period (promotion_status,starts_at,ends_at);
