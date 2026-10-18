#!/usr/bin/env bash
# Build watch-out-wednesday.zip with top-level folder `watch-out-wednesday/`, excluding everything in .distignore.
# Usage: bin/build-zip.sh [out.zip]
set -euo pipefail
slug="watch-out-wednesday"
root="$(cd "$(dirname "$0")/.." && pwd)"
out="${1:-$root/$slug.zip}"
stage="$(mktemp -d)"
trap 'rm -rf "$stage"' EXIT

mkdir "$stage/$slug"
excludes=()
while IFS= read -r line; do
  line="${line%%#*}"; line="${line//[[:space:]]/}"
  [ -n "$line" ] && excludes+=(--exclude="./${line%/}")
done < "$root/.distignore"

(cd "$root" && tar -cf - "${excludes[@]}" .) | tar -xf - -C "$stage/$slug"
rm -f "$out"

if command -v zip >/dev/null 2>&1; then
  (cd "$stage" && zip -qr "$out" "$slug")
else
  # Windows Git Bash has no zip; use Python's zipfile (set PYTHON to override).
  py="${PYTHON:-}"
  for c in python3 python py; do
    [ -z "$py" ] && "$c" -c 'import zipfile' >/dev/null 2>&1 && py="$c"
  done
  [ -n "$py" ] || { echo "Need zip or Python to build the archive." >&2; exit 1; }
  (cd "$stage" && "$py" -m zipfile -c "$out" "$slug")
fi
echo "Built $out"
