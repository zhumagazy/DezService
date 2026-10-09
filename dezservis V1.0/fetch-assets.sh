#!/usr/bin/env bash
# Downloads every file listed in assets.txt that is not in the repo yet.
# Each line: <local-path> <url> [fallback-url...]
set -u
cd "$(dirname "$0")/.."
missing=()
while read -r local urls; do
  [[ -z "$local" || "$local" == \#* ]] && continue
  [[ -s "$local" ]] && continue
  mkdir -p "$(dirname "$local")"
  ok=
  for url in $urls; do
    if curl -fsSL --retry 3 -A "Mozilla/5.0" -o "$local.part" "$url"; then
      mv "$local.part" "$local"; echo "ok   $local  <- $url"; ok=1; break
    fi
  done
  if [[ -z "$ok" ]]; then rm -f "$local.part"; missing+=("$local"); echo "FAIL $local"; fi
done < assets.txt
if (( ${#missing[@]} )); then
  printf 'Not downloaded (%d):\n' "${#missing[@]}"; printf '  %s\n' "${missing[@]}"
  exit 1
fi
