# FLEXJOB server requirements

This deployment profile follows the Information Systems department server specification.

## Runtime

- PHP 8.3
- MySQL 8.x with InnoDB and `utf8mb4`
- Apache 2.4 with `mod_rewrite`, `mod_headers`, and `.htaccess` overrides enabled
- HTTPS for every non-local environment

Required PHP extensions:

- `fileinfo`
- `mbstring`
- `openssl`
- `PDO`
- `pdo_mysql`
- `session`

The application uses Bootstrap 5.3.3 and the bundled PHPMailer 6.9.1. These are compatible with the specified PHP 8.x library profile. jQuery, DataTables, Chart.js, FullCalendar, TCPDF, and mPDF are not application dependencies and do not need to be installed.

## Environment variables

Configure these values in the web server and in the environment used by scheduled tasks:

```text
FLEXJOB_APP_URL=https://example.ac.th/flexjob
FLEXJOB_BASE_URL=/flexjob
FLEXJOB_DB_HOST=127.0.0.1
FLEXJOB_DB_PORT=3306
FLEXJOB_DB_NAME=db_flexjob
FLEXJOB_DB_USER=flexjob_app
FLEXJOB_DB_PASS=replace-with-a-strong-password
FLEXJOB_SMTP_USER=mailer@example.ac.th
FLEXJOB_SMTP_PASS=replace-with-an-app-password
FLEXJOB_PROMPTPAY_ID=replace-with-the-recipient-id
FLEXJOB_PROMPTPAY_RECIPIENT_NAME=replace-with-the-recipient-name
```

Set `FLEXJOB_TRUST_PROXY_HEADERS=1` only when the application is behind a trusted reverse proxy that overwrites `X-Forwarded-Proto`. `FLEXJOB_APP_URL` should always be set in production so email links cannot depend on the incoming Host header.

## Deployment checks

1. Import `database/schema_latest.sql` into a new MySQL database, or run `php database/migrate.php` for an existing installation.
2. Run `php scripts/check_server_requirements.php`.
3. Run every PHP smoke test in `tests` against the MySQL test database.
4. Schedule `scripts/process_email_queue.php` every minute.
5. Schedule `scripts/process_subscription_expiry.php` at least daily.
6. Confirm requests to `/.git/config`, `/database/schema_latest.sql`, `/config/config.php`, and `/docs/SERVER_REQUIREMENTS.md` return HTTP 403 or 404.
7. Confirm production responses use HTTPS and the session cookie has `Secure`, `HttpOnly`, and `SameSite=Lax`.
