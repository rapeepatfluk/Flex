# Database migrations

The canonical schema is managed through ordered SQL files in `database/migrations`.

## Commands

Run these commands from the project root:

```powershell
C:\xampp\php\php.exe database\migrate.php --status
C:\xampp\php\php.exe database\migrate.php
```

For the current database, which already has the FLEXJOB tables, register the initial schema without executing it:

```powershell
C:\xampp\php\php.exe database\migrate.php --baseline
```

After baseline, run `migrate.php` normally whenever a new migration file is added.

## Email delivery queue

Status-change and new-application emails are placed in `email_log` first, so the
browser does not wait for the SMTP server. Run this worker every minute with
Windows Task Scheduler (or another server scheduler):

```powershell
C:\xampp\php\php.exe scripts\process_email_queue.php
```

It sends up to 20 queued emails per run. Failed messages retry twice at
five-minute intervals before their status becomes `failed`.

## Latest baseline for a new machine

Use [schema_latest.sql](schema_latest.sql) for a new empty database. It contains
the latest structure through migration `0008` and only the reference data the
application needs (categories, interests, broad skills and promotion packages).
It does not include user accounts, jobs, applications, uploads or email history.

The file creates and uses `db_flexjob`, so import it only into a new database:

```powershell
Get-Content database\schema_latest.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root
```

Do not run the current migrations immediately after this import: the snapshot
already records migrations `0001` through `0008`. Run `migrate.php` only when a
new migration is added later.

## Adding a migration

Create the next ordered file in `database/migrations`, for example:

```text
0002_add_example_column.sql
```

Never edit a migration after it has been applied. The runner stores a SHA-256 checksum in `schema_migrations` and stops if an applied migration file changes.

## Notes

- `0001_initial_schema.sql` is generated from the current `db_flexjob_schema.sql` and is for a fresh database only.
- Legacy `migrate_*.sql` files remain for historical reference; their changes are already included in migration 0001.
- `email_log` does not have a Foreign Key by design.
