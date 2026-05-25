# Setup Guide

This guide documents a basic setup for running the app on modern PHP with Apache and MySQL/MariaDB on Debian-like systems. It is intentionally minimal and focused on getting the legacy code running.

## Requirements

- PHP 7.4+ with the `mysqli` extension enabled.
- Apache 2.x with mod_php (or PHP via CGI/FastCGI).
- MySQL 5.x (or compatible MariaDB).

## Configure Apache

1. Point the web root at the repo’s `qdb/` directory.
2. Ensure PHP is enabled for `.php` files.
3. (Optional) Serve under `/hqdb/` if you want cookie paths to match the original setup.

The cookie path is defined in `includes/sentinel.php` as `/hqdb/`.

## Configure the database

1. Create a database and user.
2. Apply the schema in `docs/schema.sql`.
3. Copy `qdb/local_config.example.php` to `qdb/local_config.php` and set local DB credentials.
4. Seed the first admin user.

See [database.md](database.md) for details and the SQL snippet.

`qdb/local_config.php` is ignored by Git. Environment variables are also supported:

- `QDB_DB_HOST`
- `QDB_DB_USER`
- `QDB_DB_PASS`
- `QDB_DB_NAME`
- `QDB_SESSION_COOKIE_PATH`
- `QDB_SESSION_COOKIE_SECURE`

The default session cookie path is `/hqdb/` to match the original setup. `QDB_SESSION_COOKIE_SECURE` defaults to false for LAN HTTP; set it to true only when serving over HTTPS.

## File permissions

No writable directories are required by default. If you add uploads or caching in the future, ensure Apache has write access to those locations.

## Smoke test checklist

- Visit `/` and ensure the home page renders.
- Navigate to `?latest` and confirm quotes load.
- Log in via `?admin` with the seeded admin user.
- Change the admin password from `?admin`, then log in with the new password.
