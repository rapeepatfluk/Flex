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
