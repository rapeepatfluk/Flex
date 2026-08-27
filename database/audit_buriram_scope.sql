-- FLEXJOB Buriram scope audit (read-only)
-- This file intentionally does not update existing records.

SELECT job_id, employer_user_id, job_title, work_location, work_province, work_mode, job_status
FROM jobs
WHERE COALESCE(work_province, '') <> 'บุรีรัมย์'
ORDER BY created_at DESC;

SELECT u.user_id, u.first_name, u.last_name, wp.work_province, wp.profile_visibility
FROM users u
JOIN worker_profiles wp ON wp.user_id = u.user_id
WHERE u.role = 'worker'
  AND COALESCE(wp.work_province, '') <> 'บุรีรัมย์'
ORDER BY u.user_id;
