ALTER TABLE users
  ADD COLUMN username VARCHAR(30) NULL AFTER user_id,
  ADD UNIQUE KEY uq_users_username (username);

CREATE TABLE reviews (
  review_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  application_id INT UNSIGNED NOT NULL,
  reviewer_user_id INT UNSIGNED NOT NULL,
  reviewee_user_id INT UNSIGNED NOT NULL,
  reviewer_role ENUM('worker','employer') NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  review_comment VARCHAR(1000) NULL,
  review_status ENUM('visible','hidden') NOT NULL DEFAULT 'visible',
  moderated_by_user_id INT UNSIGNED NULL,
  moderated_at DATETIME NULL,
  moderation_note VARCHAR(1000) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (review_id),
  UNIQUE KEY uq_review_application_reviewer (application_id,reviewer_user_id),
  KEY idx_reviewee_visible (reviewee_user_id,review_status,created_at),
  KEY idx_review_reviewer (reviewer_user_id,created_at),
  CONSTRAINT fk_review_application FOREIGN KEY (application_id) REFERENCES applications(application_id) ON DELETE CASCADE,
  CONSTRAINT fk_review_reviewer FOREIGN KEY (reviewer_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_review_reviewee FOREIGN KEY (reviewee_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_review_moderator FOREIGN KEY (moderated_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL,
  CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE review_reports (
  review_report_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  review_id INT UNSIGNED NOT NULL,
  reporter_user_id INT UNSIGNED NOT NULL,
  report_reason VARCHAR(500) NOT NULL,
  report_status ENUM('pending','resolved','dismissed') NOT NULL DEFAULT 'pending',
  resolved_by_user_id INT UNSIGNED NULL,
  resolved_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (review_report_id),
  UNIQUE KEY uq_review_reporter (review_id,reporter_user_id),
  KEY idx_review_report_queue (report_status,created_at),
  CONSTRAINT fk_review_report_review FOREIGN KEY (review_id) REFERENCES reviews(review_id) ON DELETE CASCADE,
  CONSTRAINT fk_review_report_reporter FOREIGN KEY (reporter_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_review_report_resolver FOREIGN KEY (resolved_by_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO reviews
  (application_id,reviewer_user_id,reviewee_user_id,reviewer_role,rating,review_comment,created_at,updated_at)
SELECT a.application_id,a.worker_user_id,j.employer_user_id,'worker',a.rating_by_worker,NULL,
       COALESCE(a.rated_by_worker_at,a.created_at),COALESCE(a.rated_by_worker_at,a.created_at)
FROM applications a
JOIN jobs j ON j.job_id=a.job_id
WHERE a.rating_by_worker IS NOT NULL;

INSERT INTO reviews
  (application_id,reviewer_user_id,reviewee_user_id,reviewer_role,rating,review_comment,created_at,updated_at)
SELECT a.application_id,j.employer_user_id,a.worker_user_id,'employer',a.rating_by_employer,NULL,
       COALESCE(a.rated_by_employer_at,a.created_at),COALESCE(a.rated_by_employer_at,a.created_at)
FROM applications a
JOIN jobs j ON j.job_id=a.job_id
WHERE a.rating_by_employer IS NOT NULL;
