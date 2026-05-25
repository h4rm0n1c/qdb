# Database Setup

This project expects a MySQL-compatible database with the tables referenced throughout the PHP code. The original database dump is unavailable, so the schema below is derived from the queries in the codebase.

## Quick start

1. Create a database (example name: `qdb`).
2. Apply the schema from [`schema.sql`](schema.sql).
3. Copy `../qdb/local_config.example.php` to `../qdb/local_config.php` and set the connection settings.
4. Create the first admin user (see the SQL snippet below).

## Schema

Run the following:

```sql
-- See schema.sql for the canonical version.
SOURCE schema.sql;
```

## Seed an admin user

Generate a hash for the initial password:

```sh
php -r 'echo password_hash("password", PASSWORD_DEFAULT), PHP_EOL;'
```

Insert the generated value:

```sql
INSERT INTO qdbusers (username, `password`, email, isadmin, enabled)
VALUES ('admin', '$2y$10$replace_with_generated_hash', 'admin@example.com', 1, 1);
```

The application accepts existing 32-character MD5 password hashes for compatibility. On successful login, it replaces the MD5 value with a `password_hash()` value.

## Migrations

Existing installs must widen `qdbusers.password` before logging in with the new code:

```sql
SOURCE migrations/20260525_password_hashes.sql;
```

## Notes

- The quote search uses `MATCH ... AGAINST`, so `qdb.quote` should be full-text indexed.
- `qdbnews.postdate` is stored as a `DATE` formatted `YYYY-MM-DD`.
- The code expects `qdbusers.enabled` to exist, even if it is not actively used.
- `qdbusers.password` is `VARCHAR(255)` to fit PHP `password_hash()` output.
