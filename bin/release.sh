#!/usr/bin/env bash
# Release a new theme version to every site running it.
#
#   bin/release.sh 2.4.0            # bump, commit, tag, push
#   bin/release.sh patch|minor|major
#
# Bumps "Version:" in style.css, commits all changes, tags v<version>
# and pushes. GitHub Actions then builds the zip and publishes the
# release; live sites auto-update within ~12 hours (or immediately via
# Dashboard → Updates → Check again).
set -euo pipefail
cd "$(dirname "$0")/.."

current=$(grep -m1 -E '^\s*Version:' style.css | sed -E 's/.*Version:[[:space:]]*//; s/[[:space:]]*$//')
arg="${1:-patch}"
IFS=. read -r MA MI PA <<<"$current"
case "$arg" in
  major) new="$((MA+1)).0.0" ;;
  minor) new="$MA.$((MI+1)).0" ;;
  patch) new="$MA.$MI.$((PA+1))" ;;
  *)     new="$arg" ;;
esac
[[ "$new" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]] || { echo "Bad version: $new"; exit 1; }

git remote get-url origin >/dev/null 2>&1 || { echo "No 'origin' remote. Add your GitHub repo first: git remote add origin git@github.com:OWNER/REPO.git"; exit 1; }
git rev-parse "v$new" >/dev/null 2>&1 && { echo "Tag v$new already exists"; exit 1; }

echo "Releasing $current -> $new"
sed -i.bak -E "s/^([[:space:]]*Version:[[:space:]]*).*/\1$new/" style.css && rm -f style.css.bak
git add -A
git commit -m "Release v$new"
git tag -a "v$new" -m "v$new"
git push origin HEAD
git push origin "v$new"
echo "Pushed v$new. GitHub is building the release; sites will update automatically."
