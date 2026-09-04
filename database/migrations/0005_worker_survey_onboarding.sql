ALTER TABLE worker_profiles
    ADD COLUMN matching_survey_required_at DATETIME NULL AFTER available_from,
    ADD COLUMN matching_survey_completed_at DATETIME NULL AFTER matching_survey_required_at,
    ADD KEY idx_worker_profiles_matching_survey (matching_survey_required_at, matching_survey_completed_at);
