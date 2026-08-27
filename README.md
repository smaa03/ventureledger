# VentureLedger

Startup portfolio analysis system built with HTML, CSS, JavaScript, PHP and MySQL.

## Setup

1. Create a MySQL database and import `database.sql`.
2. Copy `.env.example` to `.env` and add database and Google OAuth values.
3. Serve `public/` with PHP, for example: `php -S localhost:8000 -t public`.
4. In Google Cloud Console add the redirect URI: `http://localhost:8000/api/auth/google-callback.php`.

## Run the website

1. Install PHP 8.1+.
2. From the `work` folder run `php -S localhost:8000 -t public`.
3. Open `http://localhost:8000/login.php` and sign in using one of the demo accounts:
   - `ali@gmail.com` / `123456`
   - `aatif@gmail.com` / `123456`

The site works immediately in demo mode without a database; data is saved to `data/demo-store.json`. For MySQL-backed use, import `database.sql`, update `.env`, and run `database-upgrade.sql` once only for an older database.

## What it does

- Tracks portfolio company revenue, growth, runway and validation status.
- Validates reported revenue against submitted revenue evidence.
- Provides a demo dashboard when no database is configured.
- Supports Google sign-in using OAuth 2.0 (authorization-code exchange verified against Google userinfo).
- Provides authenticated dashboard, company intake, revenue validation, reports, settings, and logout flows.
- Lets new users create a password-protected account from the login page.

## Notes

The API uses PDO prepared statements. Revenue evidence should be uploaded to protected storage in a production deployment; this starter records an evidence reference and verification metadata.
