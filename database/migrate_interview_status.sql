USE db_flexjob;

ALTER TABLE applications
  MODIFY application_status ENUM('submitted', 'eligible', 'interview_passed', 'completed', 'not_selected', 'withdrawn') NOT NULL DEFAULT 'submitted';