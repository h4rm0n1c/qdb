# Security Audit

## Executive summary

This is a legacy LAN-only PHP quote database. It is provisionally functional on PHP 8, but it should not be exposed to the public internet. Slice 1 hardens authentication, session cookies, and local configuration without changing visible site behaviour. Slice 2A makes admin quote moderation actions POST-only and CSRF-protected. Slice 2B adds CSRF protection to the remaining admin forms in `qdb/common.php`.

## Confirmed findings

### Critical

- `qdb/includes/sentinel.php::authenticate()` and `qdb/includes/user.php::addUser()` / `updateUser()` used unsalted MD5 password hashes. Existing hashes are fast to crack if the database is copied.
- `docs/schema.sql` defined `qdbusers.password` as `CHAR(32)`, which only fits MD5 and blocks modern password hashes.
- `qdb/global.php` kept database credentials in the tracked application file.

### High

- `qdb/includes/library.php::jsCallback()` reflects `$_GET['callback']` directly for JSONP responses.
- `qdb/common.php::format_quote()`, `news()`, `admin_pending()`, and `admin_flagged()` render quote, comment, and news HTML from the database without escaping.
- `qdb/dumper/index.php` is web-accessible importer/scraper tooling and still contains legacy database access assumptions.

### Medium

- SQL is largely string-built across `qdb/common.php`, `qdb/includes/library.php`, and `qdb/includes/user.php`. `DbConnector::queryf()` escapes strings but is not a prepared-statement API and many queries do not use it.
- `qdb/includes/sentinel.php::getInstance()` had session cookie hardening commented out and did not regenerate the session ID after login.
- `qdb/common.php::adduser()` creates new accounts with the fixed initial password `password`.
- `qdb/includes/library.php::token()` creates a token but is unused/commented out in `qdb/vote.php`.
- `qdb/common.php::admin_pending()` can load broad moderation data and retains vestigial mod assignment behaviour.

### Low

- Search in `qdb/common.php::search()` depends on MySQL/MariaDB FULLTEXT and needs performance review after real data import.
- Several admin workflows use legacy redirects and messages that should be smoke-tested before larger refactors.

## Implemented slices

- Slice 1: authentication, sessions, and local configuration.
- Slice 2A: `qdb/vote.php` admin moderation actions `approve`, `reject`, `kill`, and `unflag` now require POST and a valid CSRF token. `qdb/includes/library.php::do_admin()` now uses an explicit action map instead of a dynamic function name.
- Slice 2B: `qdb/common.php` admin forms for change password, add user, and news post/edit now include hidden CSRF tokens and validate them before mutating state.
- Session follow-up: existing admin sessions are now validated by `userid` lookup instead of storing and rechecking password hashes in `$_SESSION`.

## Recommended patch slices

1. Completed: authentication, sessions, and local configuration.
2. Completed: CSRF protection for admin change password, add user, and news post/edit forms.
3. Review public voting/flagging CSRF behaviour and remove or replace JSONP after confirming callers.
4. Escape quote, comment, and news rendering while preserving intended formatting.
5. Move `qdb/dumper/index.php` to CLI-only tooling.
6. Incrementally replace high-risk string-built SQL with parameterized or tightly typed helpers.
7. Moderation queue pagination/limits and cleanup of mod assignment behaviour.
8. Search performance review after importing representative data.

## Manual smoke test checklist

- Load `/hqdb/` or the configured app path and confirm the home page renders.
- Load `?latest`, `?random`, `?top`, and a single quote permalink.
- Log in at `?admin` with an existing MD5-backed user and confirm the database password value changes to a `password_hash()` value.
- Log out and log back in with the same password.
- Change the password from the admin panel and confirm the old password no longer works.
- Add a moderator/admin user and confirm the new account can log in with the default password.
- Confirm the `hqdb` session cookie has `HttpOnly`, `SameSite=Lax`, and path `/hqdb/`.
- Confirm existing quote voting still returns JavaScript through `qdb/vote.php`.
- Confirm admin approve/reject/kill/unflag actions work from `?admin` and fail when attempted with old-style GET URLs.

## Open questions

- Is raw HTML in quotes/news/comments an intentional feature or legacy accident?
- Is JSONP still required by any deployed client, or can `qdb/vote.php` return JSON only?
- Should `/hqdb/` remain the canonical deployment path for all environments?
- Should `qdb/dumper/index.php` be retained at all after initial import tooling is replaced?
- What minimum PHP version should be supported beyond the current PHP 8 target?
