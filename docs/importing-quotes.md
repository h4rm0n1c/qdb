# Importing Quote Archives

This project must not import quotes during web requests. Import tooling is CLI-only and expects source archives to be cloned or downloaded outside this repository.

Do not commit third-party quote archives, generated TSV files, or `qdb/local_config.php`.

## Known sources

### lenaxia/bash_irc_quotes

Repository: `https://github.com/lenaxia/bash_irc_quotes`

The README describes this as an unofficial cleaned Bash.org IRC quote dataset. It includes `cleaned/*.txt` files, one quote per file. The first line has this shape:

```text
#104878 +(223)- [X]
```

The remaining lines are the quote body. Missing quote numbers reportedly correspond to pending/rejected quotes. Completeness is not proven.

Compile it outside this repo:

```sh
git clone https://github.com/lenaxia/bash_irc_quotes /tmp/bash_irc_quotes
python3 tools/compile_lenaxia_cleaned.py --input-dir /tmp/bash_irc_quotes/cleaned --output /tmp/bash_compiled.tsv
```

The compiler emits:

```text
quote_id<TAB>score<TAB>quote
```

Newlines inside quotes are represented as literal `\n` sequences in the TSV.

### kristopolous/bash-org-tools

Repository: `https://github.com/kristopolous/bash-org-tools`

The README describes this as a Bash.org scrape, database, and fortune file taken on March 1, 2013, with all approved quotes minus removed quotes. The documented text format is:

```text
#(quote number) +(score)- [x]
quote text
```

Inspect it outside this repo:

```sh
git clone https://github.com/kristopolous/bash-org-tools /tmp/bash-org-tools
find /tmp/bash-org-tools -maxdepth 3 -type f | sort | sed -n '1,120p'
```

Observed useful paths:

- `/tmp/bash-org-tools/sql/sqlite3.db`
- `/tmp/bash-org-tools/txt/*.txt`
- `/tmp/bash-org-tools/fortune/bashorg`

The SQLite database contains a simple `quotes(id integer, score integer, quote text)` table. A local probe on 2026-05-25 reported 20,596 rows. The easiest next import route is to export the SQLite rows to TSV with a small local converter, then feed that TSV to `tools/import_bash_tsv.php`. The `txt/*.txt` files use the same header/body shape as documented in the README and can also be converted to TSV, but that path should be sampled before a full import.

### qdb.us

The qdb.us source archive is still unresolved. Investigate Wayback/CDX snapshots later before assuming a complete upstream dataset exists.

## Importing TSV

The importer preserves original quote IDs where available and upserts by `qdb.id`. Existing IDs are updated rather than duplicated.

Imported rows use:

- `approved=1`
- `flagged=0`
- `comment=''`
- `modid=1` by default

The importer stores HTML-safe quote bodies because `qdb/common.php::format_quote()` renders quote content raw. Literal `\n` sequences are converted to line breaks, escaped with `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`, then converted with `nl2br(..., false)`.

Dry run first:

```sh
php tools/import_bash_tsv.php --file /tmp/bash_compiled.tsv --dry-run
```

Commit import:

```sh
php tools/import_bash_tsv.php --file /tmp/bash_compiled.tsv --commit
```

Optional moderator ID override:

```sh
php tools/import_bash_tsv.php --file /tmp/bash_compiled.tsv --commit --modid 1
```

## Full lenaxia example

```sh
git clone https://github.com/lenaxia/bash_irc_quotes /tmp/bash_irc_quotes
python3 tools/compile_lenaxia_cleaned.py --input-dir /tmp/bash_irc_quotes/cleaned --output /tmp/bash_compiled.tsv
php tools/import_bash_tsv.php --file /tmp/bash_compiled.tsv --dry-run
php tools/import_bash_tsv.php --file /tmp/bash_compiled.tsv --commit
```

## Source and copyright warning

These archives are third-party copies of Bash.org material. Confirm that local use, attribution, and redistribution are acceptable before importing or publishing generated data. This repository should contain import tooling and documentation only, not the quote archive itself.
