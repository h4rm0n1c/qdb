# Rendering Policy

This is the current transitional content policy. Do not mass-convert existing database rows without a separate migration plan and sample review.

## Current Helpers

- `qdb_h($value)` escapes text with `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- `qdb_text_to_html($value)` normalizes CRLF/CR to LF, escapes text with `qdb_h()`, then converts line breaks with `nl2br(..., false)`.
- `qdb_render_legacy_html($value)` marks legacy database fields that are rendered as existing HTML for now.

## Current Storage

- `qdb.quote` imported rows are currently escaped plus `nl2br` HTML from the CLI importer. Render them with `qdb_render_legacy_html()` for now to avoid double-escaping imported quotes.
- `qdbnews.post` rows may contain legacy raw HTML or escaped plus `nl2br` HTML. Newly saved news posts are stored as escaped plus `nl2br` HTML after this patch.
- `qdb.comment` should be treated as plain text and escaped on render.

## Target Policy

The long-term target is to store user/admin-authored content as plain text and escape on render with `qdb_h()` or `qdb_text_to_html()`.

Before moving to that target, audit representative rows from `qdb.quote`, `qdb.comment`, and `qdbnews.post`, then add an explicit migration. Do not blindly run `htmlspecialchars()` over all quote rows; imported quotes may already contain escaped entities and `<br />` line breaks, so a blind conversion can produce visible double-escaped text.
