CREATE TABLE job_worker_matches (
    job_id INT UNSIGNED NOT NULL,
    worker_user_id INT UNSIGNED NOT NULL,
    match_score TINYINT UNSIGNED NULL,
    data_strength TINYINT UNSIGNED NOT NULL DEFAULT 0,
    match_reasons_json TEXT NOT NULL,
    missing_required_json TEXT NOT NULL,
    required_skills_json TEXT NOT NULL,
    preferred_skills_json TEXT NOT NULL,
    calculated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (job_id, worker_user_id),
    KEY idx_job_worker_matches_job_score (job_id, match_score),
    KEY idx_job_worker_matches_worker_score (worker_user_id, match_score),
    CONSTRAINT fk_job_worker_matches_job
        FOREIGN KEY (job_id) REFERENCES jobs(job_id) ON DELETE CASCADE,
    CONSTRAINT fk_job_worker_matches_worker
        FOREIGN KEY (worker_user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
