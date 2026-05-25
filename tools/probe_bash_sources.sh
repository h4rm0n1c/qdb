#!/bin/sh
set -eu

LENAXIA_DIR="${1:-/tmp/bash_irc_quotes}"
KRISTOPOLOUS_DIR="${2:-/tmp/bash-org-tools}"

echo "lenaxia path: $LENAXIA_DIR"
if [ -d "$LENAXIA_DIR/cleaned" ]; then
	echo "  cleaned directory found: $LENAXIA_DIR/cleaned"
	echo "  compile command:"
	echo "    python3 tools/compile_lenaxia_cleaned.py --input-dir $LENAXIA_DIR/cleaned --output /tmp/bash_compiled.tsv"
else
	echo "  cleaned directory not found"
fi

echo "kristopolous path: $KRISTOPOLOUS_DIR"
if [ -d "$KRISTOPOLOUS_DIR" ]; then
	echo "  candidate files:"
	find "$KRISTOPOLOUS_DIR" -maxdepth 3 -type f | sort | sed -n '1,120p'
else
	echo "  repository directory not found"
fi
