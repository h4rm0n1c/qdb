# Security Audit

## Executive summary

This is a legacy LAN-only PHP quote database. It is provisionally functional on PHP 8, but it should not be exposed to the public internet. Slice 1 hardens authentication, session cookies, and local configuration without changing visible site behaviour. Slice 2A makes admin quote moderation actions POST-only and CSRF-protected. Slice 2B adds CSRF protection to the remaining admin forms in `qdb/common.php`. Slice 2C removes JSONP from vote actions and returns plain JSON. The legacy web dumper has been retired in favour of CLI-only import tooling. Output rendering now has explicit transitional helpers, but full database content normalization is still deferred. SQL cleanup has started with admin mutation paths.

## Confirmed findings

### Critical

- `qdb/includes/sentinel.php::authenticate()` and `qdb/includes/user.php::addUser()` / `updateUser()` used unsalted MD5 password hashes. Existing hashes are fast to crack if the database is copied.
- `docs/schema.sql` defined `qdbusers.password` as `CHAR(32)`, which only fits MD5 and blocks modern password hashes.
- `qdb/global.php` kept database credentials in the tracked application file.

### High

- Partially addressed: `qdb/common.php` now uses explicit render helpers for quotes, comments, and news. Existing quote/news HTML is still rendered as legacy HTML until stored content can be normalized.
- Retired: `qdb/dumper/index.php` was web-accessible importer/scraper tooling with legacy database access assumptions.

### Medium

- Partially addressed: SQL is largely string-built across `qdb/common.php`, `qdb/includes/library.php`, and `qdb/includes/user.php`. `DbConnector::queryf()` escapes strings but is not a prepared-statement API and many queries do not use it. Admin news mutations, admin password change, and the flagged moderation query now use prepared statements.
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
- Slice 2C: `qdb/vote.php` no longer accepts or emits JSONP callbacks. Public vote/flag actions and admin moderation actions now return `application/json`, and vote dispatch uses explicit action maps.
- Session follow-up: existing admin sessions are now validated by `userid` lookup instead of storing and rechecking password hashes in `$_SESSION`.
- Dumper retirement: the web-accessible `qdb/dumper/index.php` scraper/importer was removed. Quote imports now use CLI-only tooling in `tools/`.
- Output rendering policy: `qdb/common.php` now has `qdb_h()`, `qdb_text_to_html()`, and `qdb_render_legacy_html()` helpers. New/edited news posts are escaped before storage under the current transitional model. See `docs/rendering-policy.md`.
- SQL cleanup slice 1: `DbConnector::preparedQuery()` was added and used for admin news insert/update/select, admin password update, and the flagged-quote admin query. `adduser()` now trims user/email input and normalizes `isadmin` to `0` or `1` before calling the existing user helper.

## Recommended patch slices

1. Completed: authentication, sessions, and local configuration.
2. Completed: CSRF protection for admin change password, add user, and news post/edit forms.
3. Review public vote abuse/rate limiting and whether public voting should require CSRF or another anti-automation control.
4. Partially completed: explicit output rendering helpers and transitional news escaping are in place. Full plain-text storage normalization remains deferred.
5. Completed: retire `qdb/dumper/index.php` and use CLI-only import tooling.
6. Partially completed: admin mutation SQL cleanup slice 1 is done. Remaining SQL targets include `search()`, `format_quote()` callers and quote listing queries, `single_quote()`, `browse()`, `queue()`, remaining `User` class `queryf()`/string paths, and vote count paths in `qdb/includes/library.php`.
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
- Confirm existing quote voting still updates the score through JSON responses from `qdb/vote.php`.
- Confirm old JSONP-style vote URLs return JSON only and do not emit executable callback wrappers.
- Confirm admin approve/reject/kill/unflag actions work from `?admin` and fail when attempted with old-style GET URLs.

## Open questions

- Is raw HTML in quotes/news/comments an intentional feature or legacy accident?
- Should `/hqdb/` remain the canonical deployment path for all environments?
- What minimum PHP version should be supported beyond the current PHP 8 target?
- What public vote abuse controls are appropriate for the LAN deployment: CSRF, per-session limits, rate limits, or moderation-only voting?
- What migration strategy should normalize legacy `qdb.quote` and `qdbnews.post` rows to plain text without double-escaping imported quote content?
