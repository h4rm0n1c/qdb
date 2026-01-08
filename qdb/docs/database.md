# Database Setup

This project expects a MySQL-compatible database with the tables referenced throughout the PHP code. The original database dump is unavailable, so the schema below is derived from the queries in the codebase.

## Quick start

1. Create a database (example name: `qdb`).
2. Apply the schema from [`schema.sql`](schema.sql).
3. Update the connection settings in `global.php` (`$user`, `$pass`, `$db`, `$host`).
4. Create the first admin user (see the SQL snippet below).

## Schema

Run the following:

```sql
-- See schema.sql for the canonical version.
SOURCE schema.sql;
```

## Seed an admin user

```sql
INSERT INTO qdbusers (username, `password`, email, isadmin, enabled)
VALUES ('admin', MD5('password'), 'admin@example.com', 1, 1);
```

## Notes

- The quote search uses `MATCH ... AGAINST`, so `qdb.quote` should be full-text indexed.
- `qdbnews.postdate` is stored as a `DATE` formatted `YYYY-MM-DD`.
- The code expects `qdbusers.enabled` to exist, even if it is not actively used.
