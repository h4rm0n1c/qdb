#!/usr/bin/env python3
import argparse
import re
from pathlib import Path


HEADER_RE = re.compile(r"^#(\d+)\s+\+\((-?\d+)\)-\s+\[[Xx]\]\s*$")


def parse_file(path):
    text = path.read_text(encoding="utf-8", errors="replace")
    text = text.replace("\r\n", "\n").replace("\r", "\n")
    lines = text.split("\n")
    if not lines:
        raise ValueError("empty file")

    match = HEADER_RE.match(lines[0].strip())
    if not match:
        raise ValueError("invalid header")

    quote_id = int(match.group(1))
    score = int(match.group(2))
    quote = "\n".join(lines[1:]).strip()
    if not quote:
        raise ValueError("empty quote")

    quote = quote.replace("\t", " ")
    quote = quote.replace("\n", r"\n")
    return quote_id, score, quote


def main():
    parser = argparse.ArgumentParser(description="Compile lenaxia/bash_irc_quotes cleaned/*.txt files into TSV.")
    parser.add_argument("--input-dir", required=True, help="Path to the cleaned directory")
    parser.add_argument("--output", required=True, help="Output TSV path")
    args = parser.parse_args()

    input_dir = Path(args.input_dir)
    output = Path(args.output)
    if not input_dir.is_dir():
        raise SystemExit("input directory does not exist")

    files = sorted(input_dir.glob("*.txt"))
    written = 0
    skipped = 0
    errors = 0

    with output.open("w", encoding="utf-8", newline="\n") as handle:
        for path in files:
            try:
                quote_id, score, quote = parse_file(path)
            except ValueError:
                skipped += 1
                errors += 1
                continue

            handle.write(f"{quote_id}\t{score}\t{quote}\n")
            written += 1

    print(f"files read={len(files)}")
    print(f"written={written}")
    print(f"skipped={skipped}")
    print(f"errors={errors}")


if __name__ == "__main__":
    main()
