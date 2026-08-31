USE db_flexjob;

-- worker_profiles.skills duplicated the normalized worker_skills relationship.
-- Apply after verifying that any legacy values have matching worker_skills rows.
ALTER TABLE worker_profiles DROP COLUMN skills;
