# FLEXJOB database setup

For a new database, import the files in this order:

1. `db_flexjob_schema.sql`
2. `migrate_email.sql`
3. `migrate_password_reset.sql`
4. `migrate_matching.sql`
5. `migrate_security_hardening.sql`
6. Seed files only when sample data is required

`migrate_matching.sql` is additive and safe to run on the existing database. It keeps the legacy `worker_profiles.skills` column while structured skill data is adopted.

After migrating an existing database, run `php database/backfill_matching.php` once to copy non-empty legacy worker skills into the structured tables. Job requirements must be entered by the employer; they are not inferred from job descriptions.

SMTP credentials must not be committed to source code. Configure these environment variables for Apache/PHP:

- `FLEXJOB_SMTP_USER`
- `FLEXJOB_SMTP_PASS`

For local XAMPP development, copy `config/smtp.local.example.php` to `config/smtp.local.php` and enter a newly generated Gmail App Password. The local file is ignored by Git. Environment variables take precedence when both methods are present.
