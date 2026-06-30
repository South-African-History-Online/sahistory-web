#!/usr/bin/env bash
#
# Verify legacy .htm URLs resolve to their exact target page.
#
# Usage:
#   BASE_URL=https://sahistory.org.za ./scripts/linkfix/verify-legacy-urls.sh
#   BASE_URL=https://sahistory-web.ddev.site ./scripts/linkfix/verify-legacy-urls.sh
#
# Reads scripts/linkfix/legacy_url_tests.csv (legacy_path, expected_alias, ...).
# For each legacy URL it follows redirects and checks the FINAL path equals the
# expected alias. Run it BEFORE the fix (most will FAIL -> land on /search or
# 404) and AFTER (should PASS -> land on the exact node).
#
# Exit code is the number of failures (0 = all pass).

set -uo pipefail

BASE_URL="${BASE_URL:-https://sahistory-web.ddev.site}"
TSV="$(dirname "$0")/legacy_url_tests.tsv"
LIMIT="${LIMIT:-0}"   # 0 = all rows

if [[ ! -f "$TSV" ]]; then
  echo "Test file not found: $TSV" >&2
  exit 2
fi

pass=0; fail=0; search=0; notfound=0; n=0
echo "Base URL: $BASE_URL"
echo "----------------------------------------------------------------------"

# Process substitution (not a pipe) so counters persist in this shell.
while IFS= read -r line; do
  [[ -z "$line" ]] && continue
  n=$((n+1))
  [[ "$LIMIT" -gt 0 && "$n" -gt "$LIMIT" ]] && break

  # Tab-separated: column 1 = legacy path, column 2 = expected alias.
  legacy_path=${line%%$'\t'*}
  rest=${line#*$'\t'}
  expected=${rest%%$'\t'*}

  # URL-encode spaces only (paths may contain a literal space).
  enc_path=${legacy_path// /%20}
  final=$(curl -sk -L -o /dev/null -w '%{url_effective}' --max-time 20 "$BASE_URL/$enc_path")
  code=$(curl -sk -o /dev/null -w '%{http_code}' --max-time 20 "$BASE_URL/$enc_path")

  # Strip scheme+host to get the path for comparison.
  final_path="/${final#*://*/}"

  if [[ "$final_path" == "$expected"* ]]; then
    pass=$((pass+1))
  elif [[ "$final_path" == /search* ]]; then
    search=$((search+1))
    echo "FAIL(search)  $legacy_path"
    echo "      expected $expected  got $final_path"
  elif [[ "$code" == "404" ]]; then
    notfound=$((notfound+1))
    echo "FAIL(404)     $legacy_path  (expected $expected)"
  else
    fail=$((fail+1))
    echo "FAIL          $legacy_path"
    echo "      expected $expected  got [$code] $final_path"
  fi
done < "$TSV"

total=$((pass + search + notfound + fail))
echo "----------------------------------------------------------------------"
echo "RESULT @ $BASE_URL"
echo "  total tested:        $total"
echo "  PASS (exact target): $pass"
echo "  FAIL -> search guess: $search"
echo "  FAIL -> 404:          $notfound"
echo "  FAIL -> other:        $fail"
exit $((search + notfound + fail))
