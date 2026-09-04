ALTER TABLE jobs
  ADD COLUMN work_start_date DATE NULL AFTER work_schedule,
  ADD COLUMN work_end_date DATE NULL AFTER work_start_date,
  ADD COLUMN work_start_time TIME NULL AFTER work_end_date,
  ADD COLUMN work_end_time TIME NULL AFTER work_start_time;
